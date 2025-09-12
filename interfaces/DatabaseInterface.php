<?php
/**
 * Database interface for dependency injection
 * 
 * Provides a contract for database abstraction layers in Flourish Framework.
 * This interface ensures that database implementations can be swapped and
 * mocked for testing while maintaining consistent API access.
 * 
 * @since PHP 8.4
 */
interface DatabaseInterface
{
    /**
     * Execute a SQL query and return results
     *
     * @param string $sql SQL query to execute
     * @param array $params Parameters for prepared statements
     * @return mixed Query result
     */
    public function query(string $sql, array $params = []): mixed;

    /**
     * Execute an unbuffered query for large result sets
     *
     * @param string $sql SQL query to execute
     * @return mixed Unbuffered result
     */
    public function unbufferedQuery(string $sql): mixed;

    /**
     * Escape a value for safe SQL inclusion
     *
     * @param string $type Data type to escape
     * @param mixed $value Value to escape
     * @return string Escaped value
     */
    public function escape(string $type, mixed $value): string;

    /**
     * Get the database type
     *
     * @return string Database type (mysql, postgresql, sqlite, mongodb, etc.)
     */
    public function getType(): string;

    /**
     * Begin a database transaction
     *
     * @return void
     */
    public function beginTransaction(): void;

    /**
     * Commit the current transaction
     *
     * @return void
     */
    public function commit(): void;

    /**
     * Rollback the current transaction
     *
     * @return void
     */
    public function rollback(): void;

    /**
     * Check if currently inside a transaction
     *
     * @return bool True if inside transaction
     */
    public function isInsideTransaction(): bool;

    /**
     * Get the last insert ID
     *
     * @return int|string Last insert ID
     */
    public function getLastInsertId(): int|string;

    /**
     * Get the number of affected rows from last query
     *
     * @return int Number of affected rows
     */
    public function getAffectedRows(): int;

    /**
     * Close the database connection
     *
     * @return void
     */
    public function close(): void;

    /**
     * Enable or disable debugging
     *
     * @param bool $flag Whether to enable debugging
     * @return self For method chaining
     */
    public function enableDebugging(bool $flag): self;

    /**
     * Get total query execution time
     *
     * @return float Query time in seconds
     */
    public function getQueryTime(): float;
}