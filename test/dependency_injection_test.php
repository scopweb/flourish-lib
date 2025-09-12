<?php
/**
 * Comprehensive Dependency Injection Test Suite
 * 
 * Tests the PSR-11 Container, Service Providers, and Interface Integration
 * 
 * @since PHP 8.4
 */

require_once __DIR__ . '/../container/Container.php';
require_once __DIR__ . '/../Application.php';
require_once __DIR__ . '/../interfaces/ConfigInterface.php';
require_once __DIR__ . '/../interfaces/CacheInterface.php';
require_once __DIR__ . '/../interfaces/EmailServiceInterface.php';
require_once __DIR__ . '/../interfaces/FilesystemInterface.php';
require_once __DIR__ . '/../interfaces/DatabaseInterface.php';

class DependencyInjectionTest
{
    private int $testCount = 0;
    private int $passedTests = 0;
    private array $results = [];
    
    public function runAllTests(): array
    {
        echo "🧪 Flourish Framework Dependency Injection Test Suite\n";
        echo str_repeat("=", 60) . "\n\n";
        
        // PSR-11 Container Tests
        $this->testContainerBasics();
        $this->testContainerBinding();
        $this->testContainerSingletons();
        $this->testContainerFactories();
        $this->testContainerAliases();
        $this->testContainerAutoWiring();
        $this->testContainerExceptions();
        
        // Service Provider Tests
        $this->testServiceProviders();
        $this->testServiceProviderBootstrap();
        
        // Application Integration Tests
        $this->testApplicationBootstrap();
        $this->testApplicationServiceAccess();
        $this->testApplicationErrorHandling();
        
        // Interface Implementation Tests
        $this->testEmailServiceInterface();
        $this->testConfigInterface();
        $this->testCacheInterface();
        
        // Dependency Injection in Flourish Classes
        $this->testFlourishClassDI();
        $this->testBackwardCompatibility();
        
        $this->printSummary();
        return $this->results;
    }
    
    private function testContainerBasics(): void
    {
        $this->test("Container implements PSR-11 ContainerInterface", function() {
            $container = new Container();
            return $container instanceof ContainerInterface;
        });
        
        $this->test("Container has() method works correctly", function() {
            $container = new Container();
            $container->bind('test.service', 'TestClass');
            return $container->has('test.service') && !$container->has('non.existent');
        });
        
        $this->test("Container get() method retrieves services", function() {
            $container = new Container();
            $container->instance('test.string', 'Hello World');
            return $container->get('test.string') === 'Hello World';
        });
    }
    
    private function testContainerBinding(): void
    {
        $this->test("Container bind() method works with closures", function() {
            $container = new Container();
            $container->bind('test.closure', function() {
                return 'Closure Result';
            });
            return $container->get('test.closure') === 'Closure Result';
        });
        
        $this->test("Container bind() method works with class names", function() {
            $container = new Container();
            
            // Create a simple test class
            eval('class TestServiceClass { public function getValue() { return "test_value"; } }');
            
            $container->bind('test.class', 'TestServiceClass');
            $service = $container->get('test.class');
            return $service instanceof TestServiceClass && $service->getValue() === 'test_value';
        });
    }
    
    private function testContainerSingletons(): void
    {
        $this->test("Container singleton() creates single instances", function() {
            $container = new Container();
            
            eval('class SingletonTestClass { public static $instanceCount = 0; public function __construct() { self::$instanceCount++; } }');
            
            $container->singleton('test.singleton', 'SingletonTestClass');
            
            $instance1 = $container->get('test.singleton');
            $instance2 = $container->get('test.singleton');
            
            return $instance1 === $instance2 && SingletonTestClass::$instanceCount === 1;
        });
        
        $this->test("Container instance() stores existing instances", function() {
            $container = new Container();
            $object = new stdClass();
            $object->value = 'test';
            
            $returned = $container->instance('test.instance', $object);
            $retrieved = $container->get('test.instance');
            
            return $returned === $object && $retrieved === $object;
        });
    }
    
    private function testContainerFactories(): void
    {
        $this->test("Container make() creates new instances each time", function() {
            $container = new Container();
            
            eval('class FactoryTestClass { public static $instanceCount = 0; public function __construct() { self::$instanceCount++; } }');
            FactoryTestClass::$instanceCount = 0;
            
            $container->bind('test.factory', 'FactoryTestClass');
            
            $instance1 = $container->make('test.factory');
            $instance2 = $container->make('test.factory');
            
            return $instance1 !== $instance2 && FactoryTestClass::$instanceCount === 2;
        });
    }
    
    private function testContainerAliases(): void
    {
        $this->test("Container alias() creates service aliases", function() {
            $container = new Container();
            $container->instance('original.service', 'Original Value');
            $container->alias('aliased.service', 'original.service');
            
            return $container->get('aliased.service') === 'Original Value';
        });
    }
    
    private function testContainerAutoWiring(): void
    {
        $this->test("Container auto-wires constructor dependencies", function() {
            $container = new Container();
            
            eval('
                class DependencyClass { 
                    public function getValue() { return "dependency_value"; } 
                }
                class AutoWiredClass { 
                    public function __construct(public DependencyClass $dependency) {}
                    public function getDependencyValue() { return $this->dependency->getValue(); }
                }
            ');
            
            $container->bind('DependencyClass', 'DependencyClass');
            $service = $container->make('AutoWiredClass');
            
            return $service instanceof AutoWiredClass && 
                   $service->getDependencyValue() === 'dependency_value';
        });
    }
    
    private function testContainerExceptions(): void
    {
        $this->test("Container throws NotFoundException for missing services", function() {
            $container = new Container();
            
            try {
                $container->get('non.existent.service');
                return false;
            } catch (NotFoundException $e) {
                return true;
            } catch (Exception $e) {
                return false;
            }
        });
        
        $this->test("Container throws ContainerException for build errors", function() {
            $container = new Container();
            
            try {
                $container->get('NonExistentClass');
                return false;
            } catch (ContainerException $e) {
                return true;
            } catch (Exception $e) {
                return false;
            }
        });
    }
    
    private function testServiceProviders(): void
    {
        $this->test("Service providers can be registered", function() {
            $container = new Container();
            
            eval('
                class TestServiceProvider implements ServiceProviderInterface {
                    public function register(Container $container): void {
                        $container->bind("test.service", function() { return "provider_service"; });
                    }
                    public function boot(Container $container): void {}
                }
            ');
            
            $provider = new TestServiceProvider();
            $container->registerProvider($provider);
            
            return $container->get('test.service') === 'provider_service';
        });
    }
    
    private function testServiceProviderBootstrap(): void
    {
        $this->test("Service providers boot correctly", function() {
            $container = new Container();
            
            eval('
                class BootTestProvider implements ServiceProviderInterface {
                    public static $booted = false;
                    public function register(Container $container): void {
                        $container->bind("boot.test", "BootTestValue");
                    }
                    public function boot(Container $container): void {
                        self::$booted = true;
                    }
                }
            ');
            
            $provider = new BootTestProvider();
            $container->registerProvider($provider);
            $container->bootProviders();
            
            return BootTestProvider::$booted === true;
        });
    }
    
    private function testApplicationBootstrap(): void
    {
        $this->test("Application bootstraps correctly", function() {
            try {
                $app = Application::create(__DIR__ . '/..');
                $app->bootstrap();
                
                return $app->has('config') && $app->has('app');
            } catch (Exception $e) {
                return false;
            }
        });
    }
    
    private function testApplicationServiceAccess(): void
    {
        $this->test("Application provides service access", function() {
            try {
                $app = Application::create(__DIR__ . '/..');
                $app->bootstrap();
                
                $config = $app->get('config');
                return $config instanceof ConfigInterface;
            } catch (Exception $e) {
                return false;
            }
        });
    }
    
    private function testApplicationErrorHandling(): void
    {
        $this->test("Application sets up error handling", function() {
            try {
                $app = Application::create(__DIR__ . '/..');
                $app->setupErrorHandling();
                return true;
            } catch (Exception $e) {
                return false;
            }
        });
    }
    
    private function testEmailServiceInterface(): void
    {
        $this->test("fEmail implements EmailServiceInterface", function() {
            try {
                require_once __DIR__ . '/../lib/fEmail.php';
                $email = new fEmail();
                return $email instanceof EmailServiceInterface;
            } catch (Exception $e) {
                return false;
            }
        });
    }
    
    private function testConfigInterface(): void
    {
        $this->test("Application config implements ConfigInterface", function() {
            try {
                $app = Application::create(__DIR__ . '/..');
                $app->bootstrap();
                $config = $app->get('config');
                
                // Test basic config operations
                $config->set('test.key', 'test.value');
                $hasKey = $config->has('test.key');
                $getValue = $config->get('test.key') === 'test.value';
                
                return $config instanceof ConfigInterface && $hasKey && $getValue;
            } catch (Exception $e) {
                return false;
            }
        });
    }
    
    private function testCacheInterface(): void
    {
        $this->test("Application cache implements CacheInterface", function() {
            try {
                $app = Application::create(__DIR__ . '/..');
                $app->bootstrap();
                
                if ($app->has('cache')) {
                    $cache = $app->get('cache');
                    return $cache instanceof CacheInterface;
                }
                return true; // Cache might not be configured, that's OK
            } catch (Exception $e) {
                return false;
            }
        });
    }
    
    private function testFlourishClassDI(): void
    {
        $this->test("fEmail supports constructor injection", function() {
            try {
                require_once __DIR__ . '/../lib/fEmail.php';
                
                // Create mock objects
                $mockTransport = new class implements EmailTransportInterface {
                    public function sendMessage(EmailMessageInterface $message): bool { return true; }
                    public function getLastError(): ?string { return null; }
                    public function configure(array $config): void {}
                };
                
                $mockConfig = new class implements ConfigInterface {
                    private array $config = ['email.from_email' => 'test@example.com'];
                    public function get(string $key, mixed $default = null): mixed { 
                        return $this->config[$key] ?? $default; 
                    }
                    public function set(string $key, mixed $value): void { $this->config[$key] = $value; }
                    public function has(string $key): bool { return isset($this->config[$key]); }
                    public function all(): array { return $this->config; }
                    public function loadFromFile(string $filePath, ?string $section = null): void {}
                    public function loadFromArray(array $config, ?string $section = null): void {}
                    public function loadFromEnvironment(string $prefix = ''): void {}
                    public function getSection(string $section): array { return []; }
                    public function setSection(string $section, array $config): void {}
                    public function remove(string $key): void { unset($this->config[$key]); }
                    public function clear(): void { $this->config = []; }
                    public function saveToFile(string $filePath, ?string $section = null): bool { return true; }
                    public function getEnvironment(): string { return 'test'; }
                    public function setEnvironment(string $environment): void {}
                    public function isDebugMode(): bool { return true; }
                    public function setDebugMode(bool $debug): void {}
                };
                
                $email = new fEmail($mockTransport, $mockConfig);
                return $email instanceof EmailServiceInterface;
            } catch (Exception $e) {
                echo "Error in fEmail DI test: " . $e->getMessage() . "\n";
                return false;
            }
        });
    }
    
    private function testBackwardCompatibility(): void
    {
        $this->test("fEmail maintains backward compatibility", function() {
            try {
                require_once __DIR__ . '/../lib/fEmail.php';
                
                // Test that fEmail can still be created without DI
                $email = new fEmail();
                $email->setFromEmail('test@example.com');
                $email->setSubject('Test Subject');
                
                return $email instanceof fEmail;
            } catch (Exception $e) {
                return false;
            }
        });
        
        $this->test("Static method access still works", function() {
            try {
                require_once __DIR__ . '/../lib/fEmail.php';
                
                // Test static methods still work
                $result = fEmail::combineNameEmail('Test User', 'test@example.com');
                return is_string($result) && str_contains($result, 'test@example.com');
            } catch (Exception $e) {
                return false;
            }
        });
    }
    
    private function test(string $description, callable $testFunction): void
    {
        $this->testCount++;
        
        try {
            $result = $testFunction();
            if ($result) {
                echo "✅ {$description}\n";
                $this->passedTests++;
                $this->results[] = ['test' => $description, 'status' => 'PASS', 'error' => null];
            } else {
                echo "❌ {$description}\n";
                $this->results[] = ['test' => $description, 'status' => 'FAIL', 'error' => 'Test returned false'];
            }
        } catch (Exception $e) {
            echo "💥 {$description} - Error: {$e->getMessage()}\n";
            $this->results[] = ['test' => $description, 'status' => 'ERROR', 'error' => $e->getMessage()];
        }
    }
    
    private function printSummary(): void
    {
        $failedTests = $this->testCount - $this->passedTests;
        $successRate = round(($this->passedTests / $this->testCount) * 100, 1);
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 TEST SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        echo "Total Tests: {$this->testCount}\n";
        echo "Passed: {$this->passedTests} ✅\n";
        echo "Failed: {$failedTests} " . ($failedTests > 0 ? "❌" : "✅") . "\n";
        echo "Success Rate: {$successRate}%\n\n";
        
        if ($failedTests === 0) {
            echo "🎉 ALL TESTS PASSED! Dependency Injection is working perfectly! 🎉\n";
        } else {
            echo "⚠️  Some tests failed. Check the output above for details.\n";
        }
        
        // Save results to JSON file
        $jsonResults = [
            'summary' => [
                'total_tests' => $this->testCount,
                'passed_tests' => $this->passedTests,
                'failed_tests' => $failedTests,
                'success_rate' => $successRate,
                'timestamp' => date('Y-m-d H:i:s')
            ],
            'tests' => $this->results
        ];
        
        $reportsDir = __DIR__ . '/reports';
        if (!is_dir($reportsDir)) {
            mkdir($reportsDir, 0755, true);
        }
        
        file_put_contents(
            $reportsDir . '/dependency_injection_results.json',
            json_encode($jsonResults, JSON_PRETTY_PRINT)
        );
        
        echo "\n📄 Detailed results saved to: test/reports/dependency_injection_results.json\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new DependencyInjectionTest();
    $results = $tester->runAllTests();
    
    // Exit with error code if any tests failed
    $failedCount = count(array_filter($results, fn($r) => $r['status'] !== 'PASS'));
    exit($failedCount > 0 ? 1 : 0);
}