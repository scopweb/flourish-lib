<?php
/**
 * Flourish Query Builder - Fluent Interface for Database Queries
 * 
 * Provides a modern, fluent interface for building SQL queries while maintaining
 * full compatibility with existing fDatabase functionality and security features.
 * 
 * @since PHP 8.4
 */

require_once __DIR__ . '/../lib/fDatabase.php';

/**
 * Fluent SQL Query Builder for Flourish Framework
 * 
 * Provides an elegant, readable way to construct database queries using method chaining
 * while leveraging fDatabase's security and multi-database support.
 */
class fQueryBuilder
{
    private fDatabase $database;
    private string $table = '';
    private string $queryType = 'select';
    
    // Select components
    private array $select = ['*'];
    private array $from = [];
    private array $joins = [];
    private array $wheres = [];
    private array $groups = [];
    private array $having = [];
    private array $orders = [];
    private ?int $limit = null;
    private ?int $offset = null;
    
    // Insert/Update components
    private array $values = [];
    private array $updateValues = [];
    
    // Bindings for parameters
    private array $bindings = [];
    private int $bindingCounter = 0;
    
    public function __construct(fDatabase $database, ?string $table = null)
    {
        $this->database = $database;
        if ($table !== null) {
            $this->table = $table;
            $this->from = [$table];
        }
    }
    
    // =====================================================
    // FLUENT SELECT METHODS
    // =====================================================
    
    /**
     * Set the table to query from
     * 
     * @param string $table Table name
     * @return self
     */
    public function table(string $table): self
    {
        $this->table = $table;
        $this->from = [$table];
        return $this;
    }
    
    /**
     * Set the columns to select
     * 
     * @param array|string $columns Columns to select
     * @return self
     */
    public function select(array|string $columns = ['*']): self
    {
        $this->queryType = 'select';
        $this->select = is_array($columns) ? $columns : [$columns];
        return $this;
    }
    
    /**
     * Add a basic where clause
     * 
     * @param string $column Column name
     * @param mixed $operator Operator or value if using = 
     * @param mixed $value Value (null if operator is actually the value)
     * @return self
     */
    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        // Handle where('column', 'value') format
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add an OR where clause
     * 
     * @param string $column Column name
     * @param mixed $operator Operator or value
     * @param mixed $value Value
     * @return self
     */
    public function orWhere(string $column, mixed $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'OR'
        ];
        
        return $this;
    }
    
    /**
     * Add WHERE IN clause
     * 
     * @param string $column Column name
     * @param array $values Values for IN clause
     * @return self
     */
    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add WHERE NOT IN clause
     * 
     * @param string $column Column name
     * @param array $values Values for NOT IN clause
     * @return self
     */
    public function whereNotIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'not_in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add WHERE NULL clause
     * 
     * @param string $column Column name
     * @return self
     */
    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add WHERE NOT NULL clause
     * 
     * @param string $column Column name
     * @return self
     */
    public function whereNotNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'not_null',
            'column' => $column,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add WHERE BETWEEN clause
     * 
     * @param string $column Column name
     * @param mixed $min Minimum value
     * @param mixed $max Maximum value
     * @return self
     */
    public function whereBetween(string $column, mixed $min, mixed $max): self
    {
        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'min' => $min,
            'max' => $max,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add WHERE LIKE clause
     * 
     * @param string $column Column name
     * @param string $pattern LIKE pattern
     * @return self
     */
    public function whereLike(string $column, string $pattern): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => 'LIKE',
            'value' => $pattern,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add WHERE EXISTS clause
     * 
     * @param callable|fQueryBuilder $callback Subquery callback or builder
     * @return self
     */
    public function whereExists(callable|fQueryBuilder $callback): self
    {
        if (is_callable($callback)) {
            $subquery = new fQueryBuilder($this->database);
            $callback($subquery);
        } else {
            $subquery = $callback;
        }
        
        $this->wheres[] = [
            'type' => 'exists',
            'subquery' => $subquery,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add WHERE NOT EXISTS clause
     * 
     * @param callable|fQueryBuilder $callback Subquery callback or builder
     * @return self
     */
    public function whereNotExists(callable|fQueryBuilder $callback): self
    {
        if (is_callable($callback)) {
            $subquery = new fQueryBuilder($this->database);
            $callback($subquery);
        } else {
            $subquery = $callback;
        }
        
        $this->wheres[] = [
            'type' => 'not_exists',
            'subquery' => $subquery,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add raw WHERE clause
     * 
     * @param string $sql Raw SQL condition
     * @param array $bindings Parameter bindings
     * @return self
     */
    public function whereRaw(string $sql, array $bindings = []): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'bindings' => $bindings,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add raw OR WHERE clause
     * 
     * @param string $sql Raw SQL condition
     * @param array $bindings Parameter bindings
     * @return self
     */
    public function orWhereRaw(string $sql, array $bindings = []): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'bindings' => $bindings,
            'boolean' => 'OR'
        ];
        
        return $this;
    }
    
    /**
     * Add JOIN clause
     * 
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Join operator  
     * @param string $second Second column
     * @param string $type Join type (INNER, LEFT, RIGHT, etc.)
     * @return self
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = [
            'type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        
        return $this;
    }
    
    /**
     * Add LEFT JOIN clause
     * 
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Join operator
     * @param string $second Second column  
     * @return self
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }
    
    /**
     * Add RIGHT JOIN clause
     * 
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Join operator
     * @param string $second Second column
     * @return self
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }
    
    /**
     * Add INNER JOIN clause (alias for join)
     * 
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Join operator
     * @param string $second Second column
     * @return self
     */
    public function innerJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'INNER');
    }
    
    /**
     * Add FULL OUTER JOIN clause
     * 
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Join operator
     * @param string $second Second column
     * @return self
     */
    public function fullJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'FULL OUTER');
    }
    
    /**
     * Add CROSS JOIN clause
     * 
     * @param string $table Table to cross join
     * @return self
     */
    public function crossJoin(string $table): self
    {
        $this->joins[] = [
            'type' => 'CROSS',
            'table' => $table,
            'first' => null,
            'operator' => null,
            'second' => null
        ];
        
        return $this;
    }
    
    /**
     * Add JOIN with closure for complex conditions
     * 
     * @param string $table Table to join
     * @param callable $callback Callback for join conditions
     * @param string $type Join type
     * @return self
     */
    public function joinWhere(string $table, callable $callback, string $type = 'INNER'): self
    {
        // Create a temporary query builder for join conditions
        $joinQuery = new fQueryBuilder($this->database, '');
        $callback($joinQuery);
        
        // For now, we'll store the callback for later processing
        // This is a simplified implementation - full JOIN with WHERE would need more complex logic
        $this->joins[] = [
            'type' => $type,
            'table' => $table,
            'callback' => $callback,
            'first' => null,
            'operator' => null,
            'second' => null
        ];
        
        return $this;
    }
    
    /**
     * Add ORDER BY clause
     * 
     * @param string $column Column to order by
     * @param string $direction Direction (ASC or DESC)
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        
        return $this;
    }
    
    /**
     * Add ORDER BY DESC clause
     * 
     * @param string $column Column to order by
     * @return self
     */
    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'DESC');
    }
    
    /**
     * Add GROUP BY clause
     * 
     * @param array|string $columns Columns to group by
     * @return self
     */
    public function groupBy(array|string $columns): self
    {
        $this->groups = is_array($columns) ? $columns : [$columns];
        return $this;
    }
    
    /**
     * Add HAVING clause
     * 
     * @param string $column Column name
     * @param string $operator Operator
     * @param mixed $value Value
     * @return self
     */
    public function having(string $column, string $operator, mixed $value): self
    {
        $this->having[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Set LIMIT clause
     * 
     * @param int $limit Number of rows to limit
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    
    /**
     * Set OFFSET clause
     * 
     * @param int $offset Number of rows to skip
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }
    
    /**
     * Set LIMIT with OFFSET (pagination helper)
     * 
     * @param int $limit Number of rows
     * @param int $offset Number to skip
     * @return self
     */
    public function take(int $limit, int $offset = 0): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }
    
    // =====================================================
    // INSERT/UPDATE/DELETE METHODS
    // =====================================================
    
    /**
     * Insert data into table
     * 
     * @param array $values Associative array of column => value
     * @return self
     */
    public function insert(array $values): self
    {
        $this->queryType = 'insert';
        $this->values = $values;
        return $this;
    }
    
    /**
     * Update data in table
     * 
     * @param array $values Associative array of column => value
     * @return self
     */
    public function update(array $values): self
    {
        $this->queryType = 'update';
        $this->updateValues = $values;
        return $this;
    }
    
    /**
     * Delete from table
     * 
     * @return self
     */
    public function delete(): self
    {
        $this->queryType = 'delete';
        return $this;
    }
    
    // =====================================================
    // EXECUTION METHODS
    // =====================================================
    
    /**
     * Execute the query and return results
     * 
     * @return fResult Query results
     */
    public function get(): fResult
    {
        $sql = $this->toSql();
        $bindings = $this->getBindings();
        
        return $this->database->query($sql, ...$bindings);
    }
    
    /**
     * Get the first result
     * 
     * @return array|null First row or null
     */
    public function first(): ?array
    {
        $originalLimit = $this->limit;
        $this->limit = 1;
        
        $result = $this->get();
        $row = $result->fetchRow();
        
        $this->limit = $originalLimit;
        return $row;
    }
    
    /**
     * Get a single value from first row
     * 
     * @param string $column Column name
     * @return mixed Single value
     */
    public function value(string $column): mixed
    {
        $row = $this->first();
        return $row[$column] ?? null;
    }
    
    /**
     * Count total rows
     * 
     * @return int Total count
     */
    public function count(): int
    {
        $originalSelect = $this->select;
        $this->select = ['COUNT(*) as aggregate_count'];
        
        $result = $this->get();
        $row = $result->fetchRow();
        
        $this->select = $originalSelect;
        return (int) ($row['aggregate_count'] ?? 0);
    }
    
    /**
     * Execute insert and return success
     * 
     * @return bool Success status
     */
    public function execute(): bool
    {
        $sql = $this->toSql();
        $bindings = $this->getBindings();
        
        try {
            $this->database->query($sql, ...$bindings);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get paginated results
     * 
     * @param int $page Current page (1-based)
     * @param int $perPage Items per page
     * @return array Pagination data
     */
    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $total = $this->count();
        
        $offset = ($page - 1) * $perPage;
        $this->limit = $perPage;
        $this->offset = $offset;
        
        $results = $this->get();
        $data = [];
        foreach ($results as $row) {
            $data[] = $row;
        }
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total),
            'has_more_pages' => $page < ceil($total / $perPage)
        ];
    }
    
    // =====================================================
    // SQL GENERATION METHODS
    // =====================================================
    
    /**
     * Generate the SQL query
     * 
     * @return string Generated SQL
     */
    public function toSql(): string
    {
        $this->bindings = []; // Reset bindings
        $this->bindingCounter = 0;
        
        return match($this->queryType) {
            'select' => $this->buildSelectSql(),
            'insert' => $this->buildInsertSql(),
            'update' => $this->buildUpdateSql(),
            'delete' => $this->buildDeleteSql(),
            default => throw new fProgrammerException('Invalid query type: ' . $this->queryType)
        };
    }
    
    /**
     * Get parameter bindings
     * 
     * @return array Parameter values
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
    
    /**
     * Build SELECT SQL
     * 
     * @return string SELECT SQL
     */
    private function buildSelectSql(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->select);
        
        if (!empty($this->from)) {
            $sql .= ' FROM ' . implode(', ', $this->from);
        }
        
        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                if ($join['type'] === 'CROSS') {
                    // CROSS JOIN doesn't use ON clause
                    $sql .= " CROSS JOIN {$join['table']}";
                } elseif (isset($join['callback'])) {
                    // JOIN with closure - simplified implementation
                    $sql .= " {$join['type']} JOIN {$join['table']} ON 1=1"; // Placeholder for complex conditions
                } else {
                    // Standard JOIN with ON clause
                    $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
                }
            }
        }
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }
        
        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }
        
        if (!empty($this->having)) {
            $sql .= ' HAVING ' . $this->buildHaving();
        }
        
        if (!empty($this->orders)) {
            $orderParts = [];
            foreach ($this->orders as $order) {
                $orderParts[] = $order['column'] . ' ' . $order['direction'];
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderParts);
        }
        
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        
        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }
        
        return $sql;
    }
    
    /**
     * Build INSERT SQL
     * 
     * @return string INSERT SQL
     */
    private function buildInsertSql(): string
    {
        if (empty($this->values)) {
            throw new fProgrammerException('No values specified for INSERT');
        }
        
        $columns = array_keys($this->values);
        $placeholders = [];
        
        foreach ($this->values as $value) {
            $placeholders[] = $this->addBinding($value);
        }
        
        return "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    }
    
    /**
     * Build UPDATE SQL
     * 
     * @return string UPDATE SQL
     */
    private function buildUpdateSql(): string
    {
        if (empty($this->updateValues)) {
            throw new fProgrammerException('No values specified for UPDATE');
        }
        
        $sets = [];
        foreach ($this->updateValues as $column => $value) {
            $sets[] = $column . ' = ' . $this->addBinding($value);
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }
        
        return $sql;
    }
    
    /**
     * Build DELETE SQL
     * 
     * @return string DELETE SQL
     */
    private function buildDeleteSql(): string
    {
        $sql = "DELETE FROM {$this->table}";
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }
        
        return $sql;
    }
    
    /**
     * Build WHERE clauses
     * 
     * @return string WHERE SQL
     */
    private function buildWheres(): string
    {
        $conditions = [];
        
        foreach ($this->wheres as $index => $where) {
            $boolean = $index === 0 ? '' : ' ' . $where['boolean'] . ' ';
            
            $condition = match($where['type']) {
                'basic' => $where['column'] . ' ' . $where['operator'] . ' ' . $this->addBinding($where['value']),
                'in' => $where['column'] . ' IN (' . $this->addBindings($where['values']) . ')',
                'not_in' => $where['column'] . ' NOT IN (' . $this->addBindings($where['values']) . ')',
                'null' => $where['column'] . ' IS NULL',
                'not_null' => $where['column'] . ' IS NOT NULL',
                'between' => $where['column'] . ' BETWEEN ' . $this->addBinding($where['min']) . ' AND ' . $this->addBinding($where['max']),
                'like' => $where['column'] . ' LIKE ' . $this->addBinding($where['value']),
                'exists' => 'EXISTS (' . $this->buildSubquery($where['query']) . ')',
                'not_exists' => 'NOT EXISTS (' . $this->buildSubquery($where['query']) . ')',
                'raw' => $where['sql'], // Raw SQL is used as-is
                default => throw new fProgrammerException('Invalid where type: ' . $where['type'])
            };
            
            $conditions[] = $boolean . $condition;
        }
        
        return implode('', $conditions);
    }
    
    /**
     * Build HAVING clauses
     * 
     * @return string HAVING SQL
     */
    private function buildHaving(): string
    {
        $conditions = [];
        
        foreach ($this->having as $index => $having) {
            $boolean = $index === 0 ? '' : ' ' . $having['boolean'] . ' ';
            $condition = $having['column'] . ' ' . $having['operator'] . ' ' . $this->addBinding($having['value']);
            $conditions[] = $boolean . $condition;
        }
        
        return implode('', $conditions);
    }
    
    /**
     * Add a parameter binding and return placeholder
     * 
     * @param mixed $value Value to bind
     * @return string Parameter placeholder
     */
    private function addBinding(mixed $value): string
    {
        $this->bindings[] = $value;
        
        // Return appropriate fDatabase placeholder based on type
        return match(true) {
            is_int($value) => '%i',
            is_float($value) => '%f',
            is_bool($value) => '%b',
            is_null($value) => 'NULL',
            default => '%s'
        };
    }
    
    /**
     * Add multiple parameter bindings
     * 
     * @param array $values Values to bind
     * @return string Comma-separated placeholders
     */
    private function addBindings(array $values): string
    {
        $placeholders = [];
        foreach ($values as $value) {
            $placeholders[] = $this->addBinding($value);
        }
        return implode(', ', $placeholders);
    }
    
    /**
     * Build subquery SQL
     * 
     * @param callable|fQueryBuilder $query Subquery
     * @return string Subquery SQL
     */
    private function buildSubquery(callable|fQueryBuilder $query): string
    {
        if (is_callable($query)) {
            // Create new query builder instance for subquery
            $subquery = new fQueryBuilder($this->database, '');
            $query($subquery);
            
            // Merge subquery bindings into main query
            foreach ($subquery->getBindings() as $binding) {
                $this->bindings[] = $binding;
            }
            
            return $subquery->toSql();
        } elseif ($query instanceof fQueryBuilder) {
            // Merge subquery bindings into main query
            foreach ($query->getBindings() as $binding) {
                $this->bindings[] = $binding;
            }
            
            return $query->toSql();
        } else {
            throw new fProgrammerException('Invalid subquery type');
        }
    }
}