<?php
/**
 * Configuration interface for dependency injection
 * 
 * Provides a contract for configuration management in Flourish Framework.
 * Supports hierarchical configuration with dot notation and environment
 * variable support.
 * 
 * @since PHP 8.4
 */
interface ConfigInterface
{
    /**
     * Get a configuration value
     *
     * @param string $key Configuration key (supports dot notation)
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a configuration value
     *
     * @param string $key Configuration key (supports dot notation)
     * @param mixed $value Value to set
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if a configuration key exists
     *
     * @param string $key Configuration key to check
     * @return bool True if key exists
     */
    public function has(string $key): bool;

    /**
     * Get all configuration values
     *
     * @return array All configuration data
     */
    public function all(): array;

    /**
     * Load configuration from a file
     *
     * @param string $filePath Configuration file path
     * @param string|null $section Optional section name
     * @return void
     */
    public function loadFromFile(string $filePath, ?string $section = null): void;

    /**
     * Load configuration from an array
     *
     * @param array $config Configuration array
     * @param string|null $section Optional section name
     * @return void
     */
    public function loadFromArray(array $config, ?string $section = null): void;

    /**
     * Load configuration from environment variables
     *
     * @param string $prefix Environment variable prefix
     * @return void
     */
    public function loadFromEnvironment(string $prefix = ''): void;

    /**
     * Get configuration for a specific section
     *
     * @param string $section Section name
     * @return array Section configuration
     */
    public function getSection(string $section): array;

    /**
     * Set configuration for a specific section
     *
     * @param string $section Section name
     * @param array $config Section configuration
     * @return void
     */
    public function setSection(string $section, array $config): void;

    /**
     * Remove a configuration key
     *
     * @param string $key Configuration key to remove
     * @return void
     */
    public function remove(string $key): void;

    /**
     * Clear all configuration
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Save configuration to a file
     *
     * @param string $filePath File path to save to
     * @param string|null $section Optional section to save
     * @return bool True on success
     */
    public function saveToFile(string $filePath, ?string $section = null): bool;

    /**
     * Get the current environment
     *
     * @return string Current environment (development, production, etc.)
     */
    public function getEnvironment(): string;

    /**
     * Set the current environment
     *
     * @param string $environment Environment name
     * @return void
     */
    public function setEnvironment(string $environment): void;

    /**
     * Check if running in debug mode
     *
     * @return bool True if debug mode enabled
     */
    public function isDebugMode(): bool;

    /**
     * Enable or disable debug mode
     *
     * @param bool $debug Debug mode flag
     * @return void
     */
    public function setDebugMode(bool $debug): void;
}