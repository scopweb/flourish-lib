<?php
/**
 * Cache interface for dependency injection
 * 
 * Provides a contract for caching implementations in Flourish Framework.
 * Compatible with PSR-16 SimpleCache interface patterns while maintaining
 * Flourish-specific functionality.
 * 
 * @since PHP 8.4
 */
interface CacheInterface
{
    /**
     * Get a cached value by key
     *
     * @param string $key Cache key
     * @param mixed $default Default value if key not found
     * @return mixed Cached value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store a value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds (0 = no expiration)
     * @return bool True on success
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool;

    /**
     * Delete a cached value
     *
     * @param string $key Cache key to delete
     * @return bool True on success
     */
    public function delete(string $key): bool;

    /**
     * Clear all cached values
     *
     * @return bool True on success
     */
    public function clear(): bool;

    /**
     * Check if a key exists in cache
     *
     * @param string $key Cache key to check
     * @return bool True if key exists
     */
    public function has(string $key): bool;

    /**
     * Get multiple cached values by keys
     *
     * @param array $keys Array of cache keys
     * @param mixed $default Default value for missing keys
     * @return array Array of key => value pairs
     */
    public function getMultiple(array $keys, mixed $default = null): array;

    /**
     * Store multiple values in cache
     *
     * @param array $values Array of key => value pairs
     * @param int $ttl Time to live in seconds
     * @return bool True on success
     */
    public function setMultiple(array $values, int $ttl = 0): bool;

    /**
     * Delete multiple cached values
     *
     * @param array $keys Array of cache keys to delete
     * @return bool True on success
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * Increment a numeric cache value
     *
     * @param string $key Cache key
     * @param int $step Increment step (default 1)
     * @return int New value after increment
     */
    public function increment(string $key, int $step = 1): int;

    /**
     * Decrement a numeric cache value
     *
     * @param string $key Cache key
     * @param int $step Decrement step (default 1)  
     * @return int New value after decrement
     */
    public function decrement(string $key, int $step = 1): int;

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function getStats(): array;
}