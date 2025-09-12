<?php
/**
 * Configuration Service Provider for Flourish Framework
 * 
 * Registers configuration services in the dependency injection container,
 * supporting multiple configuration sources and environments.
 * 
 * @since PHP 8.4
 */

require_once __DIR__ . '/../container/Container.php';
require_once __DIR__ . '/../interfaces/ConfigInterface.php';

class ConfigServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        // Register main configuration service
        $container->bind('config', function(Container $c) {
            return new class implements ConfigInterface {
                private array $config = [];
                private string $environment = 'development';
                private bool $debugMode = false;
                
                public function get(string $key, mixed $default = null): mixed {
                    $keys = explode('.', $key);
                    $value = $this->config;
                    
                    foreach ($keys as $k) {
                        if (!isset($value[$k])) {
                            return $default;
                        }
                        $value = $value[$k];
                    }
                    
                    return $value;
                }
                
                public function set(string $key, mixed $value): void {
                    $keys = explode('.', $key);
                    $config = &$this->config;
                    
                    foreach ($keys as $k) {
                        if (!isset($config[$k])) {
                            $config[$k] = [];
                        }
                        $config = &$config[$k];
                    }
                    
                    $config = $value;
                }
                
                public function has(string $key): bool {
                    return $this->get($key) !== null;
                }
                
                public function all(): array {
                    return $this->config;
                }
                
                public function loadFromFile(string $filePath, ?string $section = null): void {
                    if (!file_exists($filePath)) {
                        throw new InvalidArgumentException("Configuration file not found: $filePath");
                    }
                    
                    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                    
                    switch ($extension) {
                        case 'php':
                            $data = include $filePath;
                            break;
                        case 'json':
                            $data = json_decode(file_get_contents($filePath), true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                throw new InvalidArgumentException("Invalid JSON in config file: $filePath");
                            }
                            break;
                        case 'ini':
                            $data = parse_ini_file($filePath, true);
                            break;
                        default:
                            throw new InvalidArgumentException("Unsupported configuration file format: $extension");
                    }
                    
                    if ($section) {
                        $this->setSection($section, $data);
                    } else {
                        $this->config = array_merge($this->config, $data);
                    }
                }
                
                public function loadFromArray(array $config, ?string $section = null): void {
                    if ($section) {
                        $this->setSection($section, $config);
                    } else {
                        $this->config = array_merge($this->config, $config);
                    }
                }
                
                public function loadFromEnvironment(string $prefix = ''): void {
                    foreach ($_ENV as $key => $value) {
                        if ($prefix && !str_starts_with($key, $prefix)) {
                            continue;
                        }
                        
                        $configKey = $prefix ? substr($key, strlen($prefix)) : $key;
                        $configKey = strtolower(str_replace('_', '.', $configKey));
                        
                        // Try to convert to proper type
                        if (is_numeric($value)) {
                            $value = str_contains($value, '.') ? (float) $value : (int) $value;
                        } elseif (in_array(strtolower($value), ['true', 'false'])) {
                            $value = strtolower($value) === 'true';
                        } elseif (strtolower($value) === 'null') {
                            $value = null;
                        }
                        
                        $this->set($configKey, $value);
                    }
                }
                
                public function getSection(string $section): array {
                    return $this->get($section, []);
                }
                
                public function setSection(string $section, array $config): void {
                    $this->set($section, $config);
                }
                
                public function remove(string $key): void {
                    $keys = explode('.', $key);
                    $config = &$this->config;
                    
                    for ($i = 0; $i < count($keys) - 1; $i++) {
                        if (!isset($config[$keys[$i]])) {
                            return;
                        }
                        $config = &$config[$keys[$i]];
                    }
                    
                    unset($config[end($keys)]);
                }
                
                public function clear(): void {
                    $this->config = [];
                }
                
                public function saveToFile(string $filePath, ?string $section = null): bool {
                    $data = $section ? $this->getSection($section) : $this->all();
                    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                    
                    try {
                        switch ($extension) {
                            case 'php':
                                $content = "<?php\nreturn " . var_export($data, true) . ";\n";
                                break;
                            case 'json':
                                $content = json_encode($data, JSON_PRETTY_PRINT);
                                break;
                            case 'ini':
                                $content = $this->arrayToIni($data);
                                break;
                            default:
                                throw new InvalidArgumentException("Unsupported save format: $extension");
                        }
                        
                        return file_put_contents($filePath, $content) !== false;
                    } catch (Exception $e) {
                        return false;
                    }
                }
                
                public function getEnvironment(): string {
                    return $this->environment;
                }
                
                public function setEnvironment(string $environment): void {
                    $this->environment = $environment;
                }
                
                public function isDebugMode(): bool {
                    return $this->debugMode;
                }
                
                public function setDebugMode(bool $debug): void {
                    $this->debugMode = $debug;
                }
                
                private function arrayToIni(array $array, string $prefix = ''): string {
                    $ini = '';
                    
                    foreach ($array as $key => $value) {
                        $fullKey = $prefix ? $prefix . '.' . $key : $key;
                        
                        if (is_array($value)) {
                            $ini .= "\n[{$fullKey}]\n";
                            $ini .= $this->arrayToIni($value);
                        } else {
                            $ini .= $fullKey . ' = ' . $this->formatIniValue($value) . "\n";
                        }
                    }
                    
                    return $ini;
                }
                
                private function formatIniValue(mixed $value): string {
                    if (is_bool($value)) {
                        return $value ? 'true' : 'false';
                    }
                    if (is_null($value)) {
                        return 'null';
                    }
                    if (is_string($value) && (str_contains($value, ' ') || str_contains($value, '"'))) {
                        return '"' . str_replace('"', '""', $value) . '"';
                    }
                    
                    return (string) $value;
                }
            };
        }, true); // Singleton
        
        // Alias for PSR interface
        $container->alias(ConfigInterface::class, 'config');
        
        // Register environment-specific config loaders
        $container->bind('config.development', function(Container $c) {
            $config = clone $c->get('config');
            $config->setEnvironment('development');
            $config->setDebugMode(true);
            return $config;
        });
        
        $container->bind('config.production', function(Container $c) {
            $config = clone $c->get('config');
            $config->setEnvironment('production');
            $config->setDebugMode(false);
            return $config;
        });
        
        // Register configuration validator
        $container->bind('config.validator', function(Container $c) {
            return new class {
                public function validate(ConfigInterface $config, array $rules): array {
                    $errors = [];
                    
                    foreach ($rules as $key => $rule) {
                        if ($rule['required'] && !$config->has($key)) {
                            $errors[] = "Required configuration key '$key' is missing";
                            continue;
                        }
                        
                        $value = $config->get($key);
                        if ($value === null && !$rule['required']) {
                            continue;
                        }
                        
                        if (isset($rule['type']) && !$this->validateType($value, $rule['type'])) {
                            $errors[] = "Configuration key '$key' must be of type {$rule['type']}";
                        }
                        
                        if (isset($rule['values']) && !in_array($value, $rule['values'])) {
                            $errors[] = "Configuration key '$key' must be one of: " . implode(', ', $rule['values']);
                        }
                    }
                    
                    return $errors;
                }
                
                private function validateType(mixed $value, string $type): bool {
                    return match($type) {
                        'string' => is_string($value),
                        'int', 'integer' => is_int($value),
                        'float', 'double' => is_float($value),
                        'bool', 'boolean' => is_bool($value),
                        'array' => is_array($value),
                        default => true
                    };
                }
            };
        });
    }

    public function boot(Container $container): void
    {
        $config = $container->get('config');
        
        // Load default configuration files
        $configPaths = [
            __DIR__ . '/../config/app.php',
            __DIR__ . '/../config/database.php',
            __DIR__ . '/../config/cache.php',
            __DIR__ . '/../config/email.php'
        ];
        
        foreach ($configPaths as $path) {
            if (file_exists($path)) {
                $config->loadFromFile($path);
            }
        }
        
        // Load environment variables
        $config->loadFromEnvironment('FLOURISH_');
        
        // Set environment from config or environment variable
        $environment = $_ENV['FLOURISH_ENV'] ?? $config->get('app.environment', 'development');
        $config->setEnvironment($environment);
        
        // Set debug mode
        $debug = $_ENV['FLOURISH_DEBUG'] ?? $config->get('app.debug', $environment === 'development');
        $config->setDebugMode((bool) $debug);
        
        // Load environment-specific configuration
        $envConfigPath = __DIR__ . "/../config/{$environment}.php";
        if (file_exists($envConfigPath)) {
            $config->loadFromFile($envConfigPath);
        }
    }
}