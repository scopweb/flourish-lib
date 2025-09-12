<?php
/**
 * Database type enumeration for fDatabase class
 * 
 * Provides type safety for database types supported by Flourish Framework.
 * Each database type includes validation and extension detection methods.
 * 
 * @since PHP 8.4
 */
enum DatabaseType: string 
{
    /**
     * IBM DB2 Database
     */
    case DB2 = 'db2';
    
    /**
     * Microsoft SQL Server
     */
    case MSSQL = 'mssql';
    
    /**
     * MySQL Database
     */
    case MySQL = 'mysql';
    
    /**
     * Oracle Database
     */
    case Oracle = 'oracle';
    
    /**
     * PostgreSQL Database
     */
    case PostgreSQL = 'postgresql';
    
    /**
     * SQLite Database
     */
    case SQLite = 'sqlite';

    /**
     * Get the preferred PHP extensions for this database type
     * 
     * @return array List of extensions in order of preference
     */
    public function getPreferredExtensions(): array
    {
        return match($this) {
            self::DB2 => ['ibm_db2', 'pdo_ibm'],
            self::MSSQL => ['sqlsrv', 'pdo_dblib', 'mssql', 'sybase'],
            self::MySQL => ['mysqli', 'pdo_mysql'],
            self::Oracle => ['oci8', 'pdo_oci'],
            self::PostgreSQL => ['pgsql', 'pdo_pgsql'],
            self::SQLite => ['pdo_sqlite', 'sqlite']
        };
    }

    /**
     * Check if this database type is supported on current system
     * 
     * @return bool True if at least one required extension is loaded
     */
    public function isSupported(): bool
    {
        $extensions = $this->getPreferredExtensions();
        foreach ($extensions as $extension) {
            if (extension_loaded($extension)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the best available extension for this database type
     * 
     * @return string|null The extension name or null if none available
     */
    public function getBestExtension(): ?string
    {
        $extensions = $this->getPreferredExtensions();
        foreach ($extensions as $extension) {
            if (extension_loaded($extension)) {
                return $extension;
            }
        }
        return null;
    }

    /**
     * Check if this is a modern, PHP 8.4 compatible database type
     * 
     * @return bool True if database type is recommended for PHP 8.4+
     */
    public function isModern(): bool
    {
        return match($this) {
            self::MySQL, self::PostgreSQL, self::SQLite => true,
            self::DB2, self::Oracle, self::MSSQL => false
        };
    }

    /**
     * Get display name for this database type
     * 
     * @return string Human-readable database name
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::DB2 => 'IBM DB2',
            self::MSSQL => 'Microsoft SQL Server',
            self::MySQL => 'MySQL',
            self::Oracle => 'Oracle Database',
            self::PostgreSQL => 'PostgreSQL',
            self::SQLite => 'SQLite'
        };
    }

    /**
     * Get all supported database types
     * 
     * @return array Array of all DatabaseType cases
     */
    public static function getAllTypes(): array
    {
        return self::cases();
    }

    /**
     * Get all supported database types on current system
     * 
     * @return array Array of DatabaseType cases that are supported
     */
    public static function getSupportedTypes(): array
    {
        return array_filter(self::cases(), fn($type) => $type->isSupported());
    }

    /**
     * Create DatabaseType from string value with validation
     * 
     * @param string $type Database type string
     * @return DatabaseType
     * @throws InvalidArgumentException If type is not supported
     */
    public static function fromString(string $type): self
    {
        return self::tryFrom($type) ?? throw new InvalidArgumentException("Unsupported database type: $type");
    }
}