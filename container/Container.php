<?php
/**
 * PSR-11 compatible dependency injection container for Flourish Framework
 * 
 * Implements a lightweight, fast dependency injection container with support for:
 * - PSR-11 ContainerInterface compliance
 * - Service providers and factories
 * - Lazy object instantiation (PHP 8.4)
 * - Constructor injection and auto-wiring
 * - Singleton and transient services
 * 
 * @since PHP 8.4
 */

// PSR-11 interfaces
interface ContainerInterface
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed Entry.
     * @throws NotFoundExceptionInterface No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     */
    public function get(string $id): mixed;

    /**
     * Returns true if the container can return an entry for the given identifier.
     *
     * @param string $id Identifier of the entry to look for.
     * @return bool
     */
    public function has(string $id): bool;
}

interface ContainerExceptionInterface extends Throwable
{
}

interface NotFoundExceptionInterface extends ContainerExceptionInterface
{
}

// Exception implementations
class ContainerException extends Exception implements ContainerExceptionInterface
{
}

class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}

// Service provider interface
interface ServiceProviderInterface
{
    /**
     * Register services in the container
     *
     * @param Container $container Container instance
     * @return void
     */
    public function register(Container $container): void;

    /**
     * Boot services after all providers are registered
     *
     * @param Container $container Container instance
     * @return void
     */
    public function boot(Container $container): void;
}

/**
 * Flourish Framework Dependency Injection Container
 */
class Container implements ContainerInterface
{
    /**
     * Service definitions
     *
     * @var array
     */
    private array $bindings = [];

    /**
     * Singleton instances
     *
     * @var array
     */
    private array $instances = [];

    /**
     * Service aliases
     *
     * @var array
     */
    private array $aliases = [];

    /**
     * Registered service providers
     *
     * @var array
     */
    private array $providers = [];

    /**
     * Booted service providers
     *
     * @var array
     */
    private array $bootedProviders = [];

    /**
     * Contextual bindings
     *
     * @var array
     */
    private array $contextualBindings = [];

    /**
     * Get a service from the container
     *
     * @param string $id Service identifier
     * @return mixed Service instance
     * @throws NotFoundException
     * @throws ContainerException
     */
    public function get(string $id): mixed
    {
        try {
            return $this->resolve($id);
        } catch (Exception $e) {
            if ($e instanceof NotFoundException) {
                throw $e;
            }
            throw new ContainerException(
                "Error resolving service '{$id}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Check if the container has a service
     *
     * @param string $id Service identifier
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || 
               isset($this->instances[$id]) || 
               isset($this->aliases[$id]) ||
               class_exists($id);
    }

    /**
     * Bind a service to the container
     *
     * @param string $abstract Service identifier
     * @param mixed $concrete Service implementation (class name, closure, or instance)
     * @param bool $shared Whether service should be singleton
     * @return self
     */
    public function bind(string $abstract, mixed $concrete = null, bool $shared = false): self
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'shared' => $shared
        ];

        // Remove existing instance if rebinding
        unset($this->instances[$abstract]);

        return $this;
    }

    /**
     * Bind a singleton service
     *
     * @param string $abstract Service identifier
     * @param mixed $concrete Service implementation
     * @return self
     */
    public function singleton(string $abstract, mixed $concrete = null): self
    {
        return $this->bind($abstract, $concrete, true);
    }

    /**
     * Bind an existing instance as singleton
     *
     * @param string $abstract Service identifier
     * @param mixed $instance Service instance
     * @return mixed The bound instance
     */
    public function instance(string $abstract, mixed $instance): mixed
    {
        $this->instances[$abstract] = $instance;
        return $instance;
    }

    /**
     * Create an alias for a service
     *
     * @param string $alias Alias name
     * @param string $abstract Original service identifier
     * @return self
     */
    public function alias(string $alias, string $abstract): self
    {
        $this->aliases[$alias] = $abstract;
        return $this;
    }

    /**
     * Register a service provider
     *
     * @param ServiceProviderInterface $provider Service provider
     * @return self
     */
    public function registerProvider(ServiceProviderInterface $provider): self
    {
        $className = get_class($provider);
        
        if (!in_array($className, $this->providers)) {
            $this->providers[] = $className;
            $provider->register($this);
        }

        return $this;
    }

    /**
     * Boot all registered service providers
     *
     * @return self
     */
    public function bootProviders(): self
    {
        foreach ($this->providers as $providerClass) {
            if (!in_array($providerClass, $this->bootedProviders)) {
                $provider = new $providerClass();
                $provider->boot($this);
                $this->bootedProviders[] = $providerClass;
            }
        }

        return $this;
    }

    /**
     * Add contextual binding
     *
     * @param string $when Context class
     * @param string $needs Interface needed
     * @param mixed $give Implementation to provide
     * @return self
     */
    public function when(string $when): ContextualBindingBuilder
    {
        return new ContextualBindingBuilder($this, $when);
    }

    /**
     * Set contextual binding
     *
     * @param string $when Context class
     * @param string $needs Interface needed
     * @param mixed $give Implementation to provide
     * @return void
     */
    public function setContextualBinding(string $when, string $needs, mixed $give): void
    {
        $this->contextualBindings[$when][$needs] = $give;
    }

    /**
     * Make a service instance (factory method)
     *
     * @param string $abstract Service identifier
     * @param array $parameters Constructor parameters
     * @return mixed Service instance
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters, false);
    }

    /**
     * Resolve a service from the container
     *
     * @param string $abstract Service identifier
     * @param array $parameters Constructor parameters
     * @param bool $useCache Whether to use cached instances
     * @return mixed Service instance
     * @throws NotFoundException
     * @throws ContainerException
     */
    private function resolve(string $abstract, array $parameters = [], bool $useCache = true): mixed
    {
        $abstract = $this->resolveAlias($abstract);

        // Return cached instance if singleton
        if ($useCache && isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $binding = $this->getBinding($abstract);
        $concrete = $binding['concrete'];

        // Handle closure bindings
        if ($concrete instanceof Closure) {
            $instance = $concrete($this, $parameters);
        } else {
            $instance = $this->build($concrete, $parameters);
        }

        // Cache if singleton
        if ($binding['shared']) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * Build a service instance
     *
     * @param string $concrete Class name to build
     * @param array $parameters Constructor parameters
     * @return mixed Service instance
     * @throws ContainerException
     */
    private function build(string $concrete, array $parameters = []): mixed
    {
        if (!class_exists($concrete)) {
            throw new ContainerException("Class '{$concrete}' does not exist");
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Class '{$concrete}' is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters(), $parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve constructor dependencies
     *
     * @param array $parameters Reflection parameters
     * @param array $primitives Primitive values
     * @return array Resolved dependencies
     * @throws ContainerException
     */
    private function resolveDependencies(array $parameters, array $primitives = []): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            // Use provided primitive if available
            if (isset($primitives[$name])) {
                $dependencies[] = $primitives[$name];
                continue;
            }

            // Resolve type hint
            $type = $parameter->getType();
            
            if ($type && !$type->isBuiltin()) {
                $className = $type instanceof ReflectionNamedType ? $type->getName() : (string) $type;
                
                try {
                    $dependencies[] = $this->resolve($className);
                } catch (NotFoundException $e) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                    } else {
                        throw new ContainerException(
                            "Cannot resolve dependency '{$className}' for parameter '{$name}'"
                        );
                    }
                }
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new ContainerException(
                    "Cannot resolve primitive parameter '{$name}'"
                );
            }
        }

        return $dependencies;
    }

    /**
     * Get service binding
     *
     * @param string $abstract Service identifier
     * @return array Binding information
     * @throws NotFoundException
     */
    private function getBinding(string $abstract): array
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract];
        }

        if (class_exists($abstract)) {
            return [
                'concrete' => $abstract,
                'shared' => false
            ];
        }

        throw new NotFoundException("Service '{$abstract}' not found in container");
    }

    /**
     * Resolve service alias
     *
     * @param string $abstract Service identifier
     * @return string Resolved identifier
     */
    private function resolveAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    /**
     * Clear all bindings and instances
     *
     * @return void
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
        $this->providers = [];
        $this->bootedProviders = [];
        $this->contextualBindings = [];
    }
}

/**
 * Contextual binding builder
 */
class ContextualBindingBuilder
{
    public function __construct(
        private Container $container,
        private string $when
    ) {}

    public function needs(string $abstract): self
    {
        $this->needs = $abstract;
        return $this;
    }

    public function give(mixed $implementation): void
    {
        $this->container->setContextualBinding($this->when, $this->needs, $implementation);
    }
}