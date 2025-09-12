<?php
/**
 * Database Service Provider for Flourish Framework
 * 
 * Registers database-related services in the dependency injection container,
 * including database connections, schema management, and ORM components.
 * 
 * @since PHP 8.4
 */

require_once __DIR__ . '/../container/Container.php';
require_once __DIR__ . '/../interfaces/DatabaseInterface.php';
require_once __DIR__ . '/../lib/fDatabase.php';
require_once __DIR__ . '/../lib/fSchema.php';
require_once __DIR__ . '/../lib/fORM.php';

class DatabaseServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        // Register database connection factory
        $container->bind('database', function(Container $c) {
            $config = $c->get('config');
            
            $type = $config->get('database.type', 'mysql');
            $name = $config->get('database.name');
            $user = $config->get('database.user');
            $password = $config->get('database.password');
            $host = $config->get('database.host', 'localhost');
            $port = $config->get('database.port');
            
            return new fDatabase($type, $name, $user, $password, $host, $port);
        }, true); // Singleton
        
        // Alias for PSR interface
        $container->alias(DatabaseInterface::class, 'database');
        
        // Register schema manager
        $container->bind('schema', function(Container $c) {
            return new fSchema($c->get('database'));
        }, true);
        
        // Register ORM manager (fORM is a static class, so we create a wrapper)
        $container->bind('orm', function(Container $c) {
            // fORM is a static-only class, create a wrapper for DI
            return new class($c->get('database')) {
                private fDatabase $database;
                
                public function __construct(fDatabase $database) {
                    $this->database = $database;
                }
                
                public function getDatabase(): fDatabase {
                    return $this->database;
                }
                
                public function __call(string $method, array $args) {
                    return fORM::$method(...$args);
                }
                
                public static function __callStatic(string $method, array $args) {
                    return fORM::$method(...$args);
                }
            };
        }, true);
        
        // Register result factories
        $container->bind('database.result_factory', function(Container $c) {
            return function($query) use ($c) {
                return $c->get('database')->query($query);
            };
        });
        
        // MongoDB support if configured
        $container->bind('mongodb', function(Container $c) {
            $config = $c->get('config');
            
            if (!$config->has('mongodb.connection_string')) {
                throw new ContainerException('MongoDB connection string not configured');
            }
            
            require_once __DIR__ . '/../lib/fMongoDB.php';
            return new fMongoDB($config->get('mongodb.connection_string'));
        }, true);
    }

    public function boot(Container $container): void
    {
        // Set up database connection pooling if needed
        if ($container->has('config')) {
            $config = $container->get('config');
            
            if ($config->get('database.enable_pooling', false)) {
                $database = $container->get('database');
                // Configure connection pooling settings
            }
            
            // Set up query logging if enabled
            if ($config->get('database.log_queries', false)) {
                $database = $container->get('database');
                $database->enableDebugging(true);
            }
        }
        
        // Initialize ORM with database
        if ($container->has('orm') && $container->has('database')) {
            $orm = $container->get('orm');
            $database = $container->get('database');
            // Additional ORM setup if needed
        }
    }
}