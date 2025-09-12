<?php
/**
 * Cache Service Provider for Flourish Framework
 * 
 * Registers caching services in the dependency injection container,
 * supporting multiple cache backends and PSR-16 compatibility.
 * 
 * @since PHP 8.4
 */

require_once __DIR__ . '/../container/Container.php';
require_once __DIR__ . '/../interfaces/CacheInterface.php';
require_once __DIR__ . '/../lib/fCache.php';

class CacheServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        // Register cache factory
        $container->bind('cache', function(Container $c) {
            $config = $c->get('config');
            
            $type = $config->get('cache.type', 'file');
            $cache = new fCache($type);
            
            // Configure cache settings
            if ($config->has('cache.directory')) {
                $cache->setDirectory($config->get('cache.directory'));
            }
            
            if ($config->has('cache.default_ttl')) {
                $cache->setDefaultTTL($config->get('cache.default_ttl'));
            }
            
            return $cache;
        }, true); // Singleton
        
        // Alias for PSR interface
        $container->alias(CacheInterface::class, 'cache');
        
        // Register specific cache stores
        $container->bind('cache.file', function(Container $c) {
            $config = $c->get('config');
            $cache = new fCache('file');
            
            if ($config->has('cache.file.directory')) {
                $cache->setDirectory($config->get('cache.file.directory'));
            }
            
            return $cache;
        });
        
        $container->bind('cache.memory', function(Container $c) {
            return new fCache('memory');
        });
        
        // Session-based cache for user-specific data
        $container->bind('cache.session', function(Container $c) {
            require_once __DIR__ . '/../lib/fSession.php';
            
            return new class implements CacheInterface {
                public function get(string $key, mixed $default = null): mixed {
                    return fSession::get($key, $default);
                }
                
                public function set(string $key, mixed $value, int $ttl = 0): bool {
                    fSession::set($key, $value);
                    return true;
                }
                
                public function delete(string $key): bool {
                    fSession::delete($key);
                    return true;
                }
                
                public function clear(): bool {
                    fSession::clear();
                    return true;
                }
                
                public function has(string $key): bool {
                    return fSession::get($key) !== null;
                }
                
                public function getMultiple(array $keys, mixed $default = null): array {
                    $result = [];
                    foreach ($keys as $key) {
                        $result[$key] = $this->get($key, $default);
                    }
                    return $result;
                }
                
                public function setMultiple(array $values, int $ttl = 0): bool {
                    foreach ($values as $key => $value) {
                        $this->set($key, $value, $ttl);
                    }
                    return true;
                }
                
                public function deleteMultiple(array $keys): bool {
                    foreach ($keys as $key) {
                        $this->delete($key);
                    }
                    return true;
                }
                
                public function increment(string $key, int $step = 1): int {
                    $value = (int) $this->get($key, 0);
                    $newValue = $value + $step;
                    $this->set($key, $newValue);
                    return $newValue;
                }
                
                public function decrement(string $key, int $step = 1): int {
                    $value = (int) $this->get($key, 0);
                    $newValue = $value - $step;
                    $this->set($key, $newValue);
                    return $newValue;
                }
                
                public function getStats(): array {
                    return [
                        'type' => 'session',
                        'hits' => 0,
                        'misses' => 0,
                        'size' => count($_SESSION ?? [])
                    ];
                }
            };
        });
    }

    public function boot(Container $container): void
    {
        // Set up cache warmup if configured
        if ($container->has('config')) {
            $config = $container->get('config');
            
            if ($config->get('cache.warmup_on_boot', false)) {
                $cache = $container->get('cache');
                $this->warmupCache($cache, $config);
            }
            
            // Set up cache cleanup scheduled tasks
            if ($config->get('cache.auto_cleanup', false)) {
                $cache = $container->get('cache');
                // Register cleanup tasks
            }
        }
    }
    
    private function warmupCache(CacheInterface $cache, ConfigInterface $config): void
    {
        $warmupData = $config->get('cache.warmup_data', []);
        
        foreach ($warmupData as $key => $value) {
            if (!$cache->has($key)) {
                $cache->set($key, $value);
            }
        }
    }
}