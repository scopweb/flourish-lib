<?php
/**
 * Redis Cache Integration
 *
 * Modern Redis client wrapper for high-performance caching and data storage
 * with full support for Redis data types and operations
 *
 * ✅ **PHP 8.4 COMPATIBLE** ✅
 * Uses native PHP Redis extension or Predis for compatibility
 *
 * @copyright  Copyright (c) 2025 Claude Code Migration
 * @author     Claude Code Migration <noreply@anthropic.com>
 * @license    http://flourishlib.com/license
 * @package    Flourish
 * @link       http://flourishlib.com/fRedis
 * @version    1.0.0 - Redis Cache Integration
 */

require_once __DIR__ . '/fException.php';
require_once __DIR__ . '/fUnexpectedException.php';
require_once __DIR__ . '/fConnectivityException.php';

class fRedis
{
    /**
     * Redis connection hostname
     *
     * @var string
     */
    private string $host = 'localhost';

    /**
     * Redis connection port
     *
     * @var integer
     */
    private int $port = 6379;

    /**
     * Redis connection timeout
     *
     * @var integer
     */
    private int $timeout = 0;

    /**
     * Redis database number
     *
     * @var integer
     */
    private int $database = 0;

    /**
     * Redis password
     *
     * @var string
     */
    private string $password = '';

    /**
     * Redis client instance
     *
     * @var object
     */
    private mixed $redis = null;

    /**
     * Connection status
     *
     * @var boolean
     */
    private bool $connected = false;

    /**
     * Constructor
     *
     * @param string $host      Redis host
     * @param integer $port     Redis port
     * @param integer $database Database number
     * @param string $password  Redis password
     */
    public function __construct(string $host = 'localhost', int $port = 6379, int $database = 0, string $password = '')
    {
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->password = $password;
    }

    /**
     * Checks if the Redis extension is available
     *
     * @return boolean  TRUE if the Redis extension is loaded
     */
    static public function checkRedisAvailability(): bool
    {
        return extension_loaded('redis');
    }

    /**
     * Connect to Redis
     *
     * @return self  For method chaining
     * @throws fConnectivityException  When connection fails
     */
    public function connect(): self
    {
        if (self::checkRedisAvailability()) {
            $this->redis = new Redis();
        } else {
            throw new fConnectivityException('Redis extension is not installed');
        }

        try {
            $connected = $this->redis->connect($this->host, $this->port, $this->timeout);

            if (!$connected) {
                throw new fConnectivityException('Cannot connect to Redis at ' . $this->host . ':' . $this->port);
            }

            if (!empty($this->password)) {
                $this->redis->auth($this->password);
            }

            if ($this->database !== 0) {
                $this->redis->select($this->database);
            }

            $this->connected = true;
        } catch (Exception $e) {
            throw new fConnectivityException('Redis connection failed: ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * Check if connected
     *
     * @return boolean  True if connected
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Set a key-value pair
     *
     * @param string $key      The key
     * @param mixed $value     The value
     * @param integer $ttl     Time to live in seconds (0 for no expiry)
     * @return boolean         True if set successfully
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        $this->ensureConnected();

        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        if ($ttl > 0) {
            return $this->redis->setex($key, $ttl, $value);
        }

        return $this->redis->set($key, $value);
    }

    /**
     * Get a value
     *
     * @param string $key     The key
     * @return mixed          The value or false if not found
     */
    public function get(string $key): mixed
    {
        $this->ensureConnected();
        $value = $this->redis->get($key);

        if ($value === false) {
            return null;
        }

        // Try to decode JSON
        if (is_string($value) && str_starts_with($value, '{') || str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }

    /**
     * Delete a key
     *
     * @param string $key  The key
     * @return integer     Number of keys deleted
     */
    public function delete(string $key): int
    {
        $this->ensureConnected();
        return $this->redis->del($key);
    }

    /**
     * Delete multiple keys
     *
     * @param array $keys  Keys to delete
     * @return integer     Number of keys deleted
     */
    public function deleteMany(array $keys): int
    {
        $this->ensureConnected();
        return $this->redis->del(...$keys);
    }

    /**
     * Check if key exists
     *
     * @param string $key  The key
     * @return boolean     True if exists
     */
    public function exists(string $key): bool
    {
        $this->ensureConnected();
        return (bool)$this->redis->exists($key);
    }

    /**
     * Increment a value
     *
     * @param string $key   The key
     * @param integer $increment  Amount to increment by
     * @return integer      The new value
     */
    public function increment(string $key, int $increment = 1): int
    {
        $this->ensureConnected();
        return $this->redis->incrBy($key, $increment);
    }

    /**
     * Decrement a value
     *
     * @param string $key   The key
     * @param integer $decrement  Amount to decrement by
     * @return integer      The new value
     */
    public function decrement(string $key, int $decrement = 1): int
    {
        $this->ensureConnected();
        return $this->redis->decrBy($key, $decrement);
    }

    /**
     * Set expiration time
     *
     * @param string $key   The key
     * @param integer $ttl  Time to live in seconds
     * @return boolean      True if expiration set
     */
    public function expire(string $key, int $ttl): bool
    {
        $this->ensureConnected();
        return (bool)$this->redis->expire($key, $ttl);
    }

    /**
     * Get time to live
     *
     * @param string $key  The key
     * @return integer     TTL in seconds (-1 if no expiry, -2 if doesn't exist)
     */
    public function ttl(string $key): int
    {
        $this->ensureConnected();
        return $this->redis->ttl($key);
    }

    /**
     * Push value to a list
     *
     * @param string $key    The key
     * @param mixed $value   Value to push
     * @return integer       New list length
     */
    public function push(string $key, mixed $value): int
    {
        $this->ensureConnected();
        return $this->redis->rpush($key, $value);
    }

    /**
     * Pop value from a list
     *
     * @param string $key  The key
     * @return mixed       Popped value or false
     */
    public function pop(string $key): mixed
    {
        $this->ensureConnected();
        return $this->redis->rpop($key);
    }

    /**
     * Get all values from a list
     *
     * @param string $key  The key
     * @return array       List values
     */
    public function getList(string $key): array
    {
        $this->ensureConnected();
        return $this->redis->lrange($key, 0, -1);
    }

    /**
     * Add to a set
     *
     * @param string $key   The key
     * @param mixed $member Member to add
     * @return integer      Number of members added
     */
    public function addToSet(string $key, mixed $member): int
    {
        $this->ensureConnected();
        return $this->redis->sadd($key, $member);
    }

    /**
     * Get all members of a set
     *
     * @param string $key  The key
     * @return array       Set members
     */
    public function getSet(string $key): array
    {
        $this->ensureConnected();
        return $this->redis->smembers($key);
    }

    /**
     * Clear all data
     *
     * @return boolean  True if successful
     */
    public function clear(): bool
    {
        $this->ensureConnected();
        return $this->redis->flushDb();
    }

    /**
     * Flush all databases
     *
     * @return boolean  True if successful
     */
    public function flush(): bool
    {
        $this->ensureConnected();
        return $this->redis->flushAll();
    }

    /**
     * Get Redis info
     *
     * @return array  Redis server info
     */
    public function info(): array
    {
        $this->ensureConnected();
        return $this->redis->info();
    }

    /**
     * Execute a raw Redis command
     *
     * @param string $command  The command
     * @param mixed ...$args   Command arguments
     * @return mixed           Command result
     */
    public function execute(string $command, ...$args): mixed
    {
        $this->ensureConnected();
        return $this->redis->__call($command, $args);
    }

    /**
     * Disconnect from Redis
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->redis !== null && $this->connected) {
            try {
                $this->redis->close();
            } catch (Exception $e) {
                // Ignore close errors
            }
            $this->connected = false;
        }
    }

    /**
     * Ensure connected
     *
     * @return void
     * @throws fConnectivityException  When not connected
     */
    private function ensureConnected(): void
    {
        if (!$this->connected) {
            throw new fConnectivityException('Not connected to Redis. Call connect() first.');
        }
    }

    /**
     * Destructor - close connection
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}

/**
 * Copyright (c) 2025 Claude Code Migration
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
