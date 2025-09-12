<?php
/**
 * Static Facade for Flourish Query Builder
 * 
 * Provides a clean, static interface to the Query Builder functionality
 * for improved developer experience and Laravel-like syntax.
 * 
 * @since PHP 8.4
 */

require_once __DIR__ . '/fQueryBuilder.php';
require_once __DIR__ . '/fRawExpression.php';

/**
 * Static Database Facade
 * 
 * Provides convenient static methods for database operations using
 * the underlying fDatabase and fQueryBuilder functionality.
 */
class fDB
{
    private static ?fDatabase $database = null;
    private static array $connections = [];
    
    /**
     * Set the default database connection
     * 
     * @param fDatabase $database Database instance
     * @return void
     */
    public static function setDatabase(fDatabase $database): void
    {
        self::$database = $database;
    }
    
    /**
     * Get the default database connection
     * 
     * @return fDatabase Database instance
     * @throws fProgrammerException If no database is set
     */
    public static function getDatabase(): fDatabase
    {
        if (self::$database === null) {
            throw new fProgrammerException('No database connection set. Call fDB::setDatabase() first.');
        }
        
        return self::$database;
    }
    
    /**
     * Add a named database connection
     * 
     * @param string $name Connection name
     * @param fDatabase $database Database instance
     * @return void
     */
    public static function addConnection(string $name, fDatabase $database): void
    {
        self::$connections[$name] = $database;
    }
    
    /**
     * Get a named database connection
     * 
     * @param string $name Connection name
     * @return fDatabase Database instance
     * @throws fProgrammerException If connection doesn't exist
     */
    public static function connection(string $name): fDatabase
    {
        if (!isset(self::$connections[$name])) {
            throw new fProgrammerException("Database connection '{$name}' not found.");
        }
        
        return self::$connections[$name];
    }
    
    /**
     * Create a Query Builder for a table
     * 
     * @param string $table Table name
     * @return fQueryBuilder Query Builder instance
     */
    public static function table(string $table): fQueryBuilder
    {
        return self::getDatabase()->table($table);
    }
    
    /**
     * Create a SELECT Query Builder
     * 
     * @param array|string $columns Columns to select
     * @return fQueryBuilder Query Builder instance
     */
    public static function select(array|string $columns = ['*']): fQueryBuilder
    {
        return self::getDatabase()->select($columns);
    }
    
    /**
     * Execute a raw SQL query (backward compatibility)
     * 
     * @param string $sql SQL query
     * @param mixed ...$params Query parameters
     * @return fResult Query result
     */
    public static function query(string $sql, ...$params): fResult
    {
        return self::getDatabase()->query($sql, ...$params);
    }
    
    /**
     * Create a raw SQL expression
     * 
     * @param string $expression Raw SQL expression
     * @return fRawExpression Raw expression object
     */
    public static function raw(string $expression): fRawExpression
    {
        return new fRawExpression($expression);
    }
    
    // =====================================================
    // FLUENT QUERY METHODS
    // =====================================================
    
    /**
     * Start a SELECT query for a table
     * 
     * @param string $table Table name
     * @param array|string $columns Columns to select
     * @return fQueryBuilder Query Builder instance
     */
    public static function selectFrom(string $table, array|string $columns = ['*']): fQueryBuilder
    {
        return self::table($table)->select($columns);
    }
    
    /**
     * Start an INSERT query for a table
     * 
     * @param string $table Table name
     * @param array $values Values to insert
     * @return bool Success status
     */
    public static function insertInto(string $table, array $values): bool
    {
        return self::table($table)->insert($values)->execute();
    }
    
    /**
     * Start an UPDATE query for a table
     * 
     * @param string $table Table name
     * @param array $values Values to update
     * @return fQueryBuilder Query Builder instance for chaining WHERE clauses
     */
    public static function updateTable(string $table, array $values): fQueryBuilder
    {
        return self::table($table)->update($values);
    }
    
    /**
     * Start a DELETE query for a table
     * 
     * @param string $table Table name
     * @return fQueryBuilder Query Builder instance for chaining WHERE clauses
     */
    public static function deleteFrom(string $table): fQueryBuilder
    {
        return self::table($table)->delete();
    }
    
    // =====================================================
    // COMMON AGGREGATE FUNCTIONS
    // =====================================================
    
    /**
     * Count rows in a table
     * 
     * @param string $table Table name
     * @param string $column Column to count (default: *)
     * @return int Row count
     */
    public static function count(string $table, string $column = '*'): int
    {
        $result = self::selectFrom($table, [self::raw("COUNT({$column}) as count")])->first();
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Get maximum value from a column
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @return mixed Maximum value
     */
    public static function max(string $table, string $column): mixed
    {
        $result = self::selectFrom($table, [self::raw("MAX({$column}) as max_val")])->first();
        return $result['max_val'] ?? null;
    }
    
    /**
     * Get minimum value from a column
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @return mixed Minimum value
     */
    public static function min(string $table, string $column): mixed
    {
        $result = self::selectFrom($table, [self::raw("MIN({$column}) as min_val")])->first();
        return $result['min_val'] ?? null;
    }
    
    /**
     * Get average value from a column
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @return float Average value
     */
    public static function avg(string $table, string $column): float
    {
        $result = self::selectFrom($table, [self::raw("AVG({$column}) as avg_val")])->first();
        return (float) ($result['avg_val'] ?? 0);
    }
    
    /**
     * Get sum of values from a column
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @return mixed Sum value
     */
    public static function sum(string $table, string $column): mixed
    {
        $result = self::selectFrom($table, [self::raw("SUM({$column}) as sum_val")])->first();
        return $result['sum_val'] ?? null;
    }
    
    // =====================================================
    // TRANSACTION METHODS
    // =====================================================
    
    /**
     * Start a database transaction
     * 
     * @return void
     */
    public static function beginTransaction(): void
    {
        self::getDatabase()->query('BEGIN');
    }
    
    /**
     * Commit the current transaction
     * 
     * @return void
     */
    public static function commit(): void
    {
        self::getDatabase()->query('COMMIT');
    }
    
    /**
     * Rollback the current transaction
     * 
     * @return void
     */
    public static function rollback(): void
    {
        self::getDatabase()->query('ROLLBACK');
    }
    
    /**
     * Execute a callback within a transaction
     * 
     * @param callable $callback Callback to execute
     * @return mixed Callback return value
     * @throws Exception Re-throws any exception after rollback
     */
    public static function transaction(callable $callback): mixed
    {
        self::beginTransaction();
        
        try {
            $result = $callback(self::getDatabase());
            self::commit();
            return $result;
        } catch (Exception $e) {
            self::rollback();
            throw $e;
        }
    }
    
    // =====================================================
    // UTILITY METHODS
    // =====================================================
    
    /**
     * Check if a table exists
     * 
     * @param string $table Table name
     * @return bool True if table exists
     */
    public static function hasTable(string $table): bool
    {
        try {
            self::getDatabase()->query("SELECT 1 FROM {$table} LIMIT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get all tables in the database
     * 
     * @return array List of table names
     */
    public static function getTables(): array
    {
        $database = self::getDatabase();
        
        // This is a simplified version - fDatabase has more sophisticated schema introspection
        try {
            $sql = match($database->getType()) {
                'mysql' => "SHOW TABLES",
                'postgresql' => "SELECT tablename FROM pg_tables WHERE schemaname = 'public'",
                'sqlite' => "SELECT name FROM sqlite_master WHERE type='table'",
                default => "SHOW TABLES" // fallback
            };
            
            $result = $database->query($sql);
            $tables = [];
            
            foreach ($result as $row) {
                $tables[] = array_values($row)[0]; // Get first column value
            }
            
            return $tables;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Escape a string for SQL (backward compatibility)
     * 
     * @param string $string String to escape
     * @return string Escaped string
     */
    public static function escape(string $string): string
    {
        return self::getDatabase()->escape('%s', $string);
    }
    
    /**
     * Get the last inserted ID
     * 
     * @return int|null Last insert ID
     */
    public static function lastInsertId(): ?int
    {
        // This would need to be implemented based on fDatabase's existing functionality
        // For now, return null as placeholder
        return null;
    }
    
    // =====================================================
    // DEBUGGING AND DEVELOPMENT
    // =====================================================
    
    /**
     * Enable query debugging
     * 
     * @param bool $enable Enable debugging
     * @return void
     */
    public static function enableDebugging(bool $enable = true): void
    {
        // fDatabase doesn't have a standard debug mode, but we can add logging here
        // For now, this is a placeholder for future enhancement
    }
    
    /**
     * Get the underlying fDatabase instance (for advanced usage)
     * 
     * @return fDatabase Database instance
     */
    public static function getConnection(): fDatabase
    {
        return self::getDatabase();
    }
    
    /**
     * Reset all connections (useful for testing)
     * 
     * @return void
     */
    public static function reset(): void
    {
        self::$database = null;
        self::$connections = [];
    }
}