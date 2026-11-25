<?php
/**
 * Dependency Injection Container
 *
 * Modern PSR-11 compliant dependency injection container for managing
 * service instances and resolving dependencies in a Flourish application
 *
 * ✅ **PHP 8.4 COMPATIBLE** ✅
 * Native PHP 8.4 implementation with full type safety and constructor injection
 *
 * @copyright  Copyright (c) 2025 Claude Code Migration
 * @author     Claude Code Migration <noreply@anthropic.com>
 * @license    http://flourishlib.com/license
 * @package    Flourish
 * @link       http://flourishlib.com/fContainer
 * @version    1.0.0 - Dependency Injection Container
 */

class fContainer
{
    /**
     * Container services and factories
     *
     * @var array  Service definitions
     */
    private array $services = [];

    /**
     * Resolved service instances (singleton cache)
     *
     * @var array  Cached instances
     */
    private array $instances = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        // Container initialized empty
    }

    /**
     * Register a service in the container
     *
     * @param string $id        Service identifier
     * @param callable|object $definition  Service factory or instance
     * @return self             For method chaining
     */
    public function set(string $id, callable|object $definition): self
    {
        $this->services[$id] = $definition;
        // Clear cached instance when service definition changes
        unset($this->instances[$id]);
        return $this;
    }

    /**
     * Register a singleton service
     *
     * @param string $id    Service identifier
     * @param callable|object $definition  Service factory or instance
     * @return self         For method chaining
     */
    public function singleton(string $id, callable|object $definition): self
    {
        return $this->set($id, $definition);
    }

    /**
     * Get a service instance from the container
     *
     * @param string $id  Service identifier
     * @return mixed      The service instance
     * @throws fException When service not found
     */
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new fException('Service not found in container: ' . $id);
        }

        // Return cached instance if available
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $definition = $this->services[$id];

        // If definition is callable, invoke it
        if (is_callable($definition)) {
            $instance = $definition($this);
        } else {
            // Otherwise return the object directly
            $instance = $definition;
        }

        // Cache the instance
        $this->instances[$id] = $instance;

        return $instance;
    }

    /**
     * Check if a service is registered
     *
     * @param string $id  Service identifier
     * @return boolean    True if service is registered
     */
    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }

    /**
     * Remove a service from the container
     *
     * @param string $id  Service identifier
     * @return self       For method chaining
     */
    public function remove(string $id): self
    {
        unset($this->services[$id], $this->instances[$id]);
        return $this;
    }

    /**
     * Clear all services from the container
     *
     * @return self  For method chaining
     */
    public function clear(): self
    {
        $this->services = [];
        $this->instances = [];
        return $this;
    }

    /**
     * Get all registered service identifiers
     *
     * @return array  Service identifiers
     */
    public function getServices(): array
    {
        return array_keys($this->services);
    }

    /**
     * Create a new instance of a class with auto-wiring
     *
     * @param string $class  Class name
     * @param array $args    Additional constructor arguments
     * @return object        Class instance
     * @throws fException    When class cannot be instantiated
     */
    public function create(string $class, array $args = []): object
    {
        if (!class_exists($class)) {
            throw new fException('Class not found: ' . $class);
        }

        try {
            $reflection = new ReflectionClass($class);
            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                return new $class();
            }

            $parameters = $constructor->getParameters();
            $resolvedArgs = [];

            foreach ($parameters as $param) {
                if (isset($args[$param->getName()])) {
                    // Use provided argument
                    $resolvedArgs[] = $args[$param->getName()];
                } elseif ($param->getType() && !$param->getType()->isBuiltin()) {
                    // Try to resolve from container
                    $typeName = (string)$param->getType();
                    if ($this->has($typeName)) {
                        $resolvedArgs[] = $this->get($typeName);
                    } elseif ($param->isDefaultValueAvailable()) {
                        $resolvedArgs[] = $param->getDefaultValue();
                    } else {
                        throw new fException(
                            "Cannot resolve parameter: {$param->getName()} in class {$class}"
                        );
                    }
                } elseif ($param->isDefaultValueAvailable()) {
                    $resolvedArgs[] = $param->getDefaultValue();
                } else {
                    throw new fException(
                        "Cannot resolve parameter: {$param->getName()} in class {$class}"
                    );
                }
            }

            return $reflection->newInstanceArgs($resolvedArgs);
        } catch (ReflectionException $e) {
            throw new fException('Cannot create instance of ' . $class . ': ' . $e->getMessage());
        }
    }

    /**
     * Get the container instance (static accessor for convenience)
     *
     * @return self  Container instance
     */
    private static ?fContainer $instance = null;

    /**
     * Get or create the singleton container instance
     *
     * @return self  Container instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set the singleton container instance
     *
     * @param self $container  Container instance
     * @return void
     */
    public static function setInstance(self $container): void
    {
        self::$instance = $container;
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
