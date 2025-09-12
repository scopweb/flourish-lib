<?php
/**
 * Flourish Framework Application Container
 * 
 * Main application class that bootstraps the framework with dependency injection,
 * service providers, and modern PHP 8.4 features.
 * 
 * @since PHP 8.4
 */

require_once __DIR__ . '/container/Container.php';
require_once __DIR__ . '/providers/ConfigServiceProvider.php';
require_once __DIR__ . '/providers/DatabaseServiceProvider.php';
require_once __DIR__ . '/providers/CacheServiceProvider.php';
require_once __DIR__ . '/providers/EmailServiceProvider.php';
require_once __DIR__ . '/providers/FilesystemServiceProvider.php';

class Application extends Container
{
    private static ?self $instance = null;
    private array $serviceProviders = [];
    private bool $booted = false;
    private string $basePath;
    
    public function __construct(string $basePath = null)
    {
        $this->basePath = $basePath ?? dirname(__DIR__);
        $this->registerBaseBindings();
        $this->registerCoreServiceProviders();
    }
    
    /**
     * Get the application instance (Singleton pattern)
     */
    public static function getInstance(string $basePath = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($basePath);
        }
        
        return self::$instance;
    }
    
    /**
     * Create a new application instance
     */
    public static function create(string $basePath = null): self
    {
        return new self($basePath);
    }
    
    /**
     * Bootstrap the application
     */
    public function bootstrap(): self
    {
        if (!$this->booted) {
            $this->bootProviders();
            $this->booted = true;
        }
        
        return $this;
    }
    
    /**
     * Register base framework bindings
     */
    private function registerBaseBindings(): void
    {
        // Register the application instance
        $this->instance('app', $this);
        $this->instance(Application::class, $this);
        $this->instance(Container::class, $this);
        
        // Register base path
        $this->instance('path.base', $this->basePath);
        $this->instance('path.lib', $this->basePath . '/lib');
        $this->instance('path.config', $this->basePath . '/config');
        $this->instance('path.storage', $this->basePath . '/storage');
        $this->instance('path.temp', sys_get_temp_dir());
    }
    
    /**
     * Register core service providers
     */
    private function registerCoreServiceProviders(): void
    {
        $this->register(new ConfigServiceProvider());
        $this->register(new DatabaseServiceProvider());
        $this->register(new CacheServiceProvider());
        $this->register(new EmailServiceProvider());
        $this->register(new FilesystemServiceProvider());
    }
    
    /**
     * Register a service provider
     */
    public function register(ServiceProviderInterface $provider): self
    {
        $className = get_class($provider);
        
        if (!in_array($className, $this->serviceProviders)) {
            $this->serviceProviders[] = $className;
            $provider->register($this);
            
            if ($this->booted) {
                $provider->boot($this);
            }
        }
        
        return $this;
    }
    
    /**
     * Boot all registered service providers
     */
    public function bootProviders(): self
    {
        foreach ($this->serviceProviders as $providerClass) {
            $provider = new $providerClass();
            $provider->boot($this);
        }
        
        return $this;
    }
    
    /**
     * Create default configuration structure
     */
    public function createDefaultConfig(): void
    {
        $configPath = $this->get('path.config');
        
        if (!is_dir($configPath)) {
            mkdir($configPath, 0755, true);
        }
        
        $configs = [
            'app.php' => [
                'name' => 'Flourish Application',
                'environment' => 'development',
                'debug' => true,
                'timezone' => 'UTC',
                'locale' => 'en_US'
            ],
            'database.php' => [
                'type' => 'mysql',
                'host' => 'localhost',
                'port' => 3306,
                'name' => 'flourish_app',
                'user' => 'root',
                'password' => '',
                'enable_pooling' => false,
                'log_queries' => false
            ],
            'cache.php' => [
                'type' => 'file',
                'directory' => $this->get('path.storage') . '/cache',
                'default_ttl' => 3600,
                'warmup_on_boot' => false,
                'auto_cleanup' => true
            ],
            'email.php' => [
                'transport' => 'mail',
                'from_email' => 'noreply@example.com',
                'from_name' => 'Flourish Application',
                'debug' => false
            ],
            'smtp.php' => [
                'host' => 'localhost',
                'port' => 587,
                'secure' => true,
                'username' => '',
                'password' => '',
                'timeout' => 10
            ]
        ];
        
        foreach ($configs as $file => $data) {
            $filePath = $configPath . '/' . $file;
            if (!file_exists($filePath)) {
                $content = "<?php\nreturn " . var_export($data, true) . ";\n";
                file_put_contents($filePath, $content);
            }
        }
    }
    
    /**
     * Set up error handling
     */
    public function setupErrorHandling(): self
    {
        $config = $this->get('config');
        
        if ($config->isDebugMode()) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }
        
        // Set up exception handler
        set_exception_handler([$this, 'handleException']);
        
        // Set up error handler
        set_error_handler([$this, 'handleError']);
        
        return $this;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public function handleException(Throwable $exception): void
    {
        $config = $this->get('config');
        
        if ($config->isDebugMode()) {
            echo "<h1>Uncaught Exception</h1>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
            echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
            echo "<h2>Stack Trace</h2>";
            echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        } else {
            echo "<h1>Application Error</h1>";
            echo "<p>An error occurred while processing your request.</p>";
        }
        
        exit(1);
    }
    
    /**
     * Handle PHP errors
     */
    public function handleError(int $level, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }
        
        throw new ErrorException($message, 0, $level, $file, $line);
    }
    
    /**
     * Run the application
     */
    public function run(): void
    {
        $this->bootstrap()
             ->setupErrorHandling();
        
        // Application is now ready to use
        // Services can be accessed via $this->get('service_name')
    }
    
    /**
     * Terminate the application gracefully
     */
    public function terminate(): void
    {
        // Cleanup resources
        if ($this->has('temp.manager')) {
            $this->get('temp.manager')->cleanup();
        }
        
        // Flush caches if needed
        if ($this->has('cache')) {
            // Optional cache cleanup
        }
    }
    
    /**
     * Magic method to access services as properties
     */
    public function __get(string $name): mixed
    {
        return $this->get($name);
    }
    
    /**
     * Check if service exists as property
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }
}