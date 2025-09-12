<?php
/**
 * fQueryBuilder Comprehensive Test Suite
 * 
 * Tests for the new Query Builder fluent interface functionality
 * These tests focus on SQL generation and logic validation without requiring a real database
 */

// Load required classes
$classesDir = dirname(__DIR__);
require_once $classesDir . '/lib/fCore.php';
require_once $classesDir . '/lib/fException.php';
require_once $classesDir . '/lib/fExpectedException.php';
require_once $classesDir . '/lib/fUnexpectedException.php';
require_once $classesDir . '/lib/fValidationException.php';
require_once $classesDir . '/lib/fConnectivityException.php';
require_once $classesDir . '/lib/fEnvironmentException.php';
require_once $classesDir . '/lib/fSQLException.php';
require_once $classesDir . '/lib/fResult.php';
require_once $classesDir . '/lib/fUnbufferedResult.php';
require_once $classesDir . '/lib/fStatement.php';
require_once $classesDir . '/lib/fDatabase.php';
require_once $classesDir . '/querybuilder/fQueryBuilder.php';
require_once $classesDir . '/querybuilder/fRawExpression.php';
require_once $classesDir . '/querybuilder/fDB.php';

// Mock fDatabase class for testing SQL generation without real database
class MockfDatabase extends fDatabase {
    public function __construct() {
        // Don't call parent constructor to avoid database connection
    }
    
    public function getType() {
        return 'mysql';
    }
    
    public function table(string $table): fQueryBuilder {
        return new fQueryBuilder($this, $table);
    }
    
    public function select(array|string $columns = ['*']): fQueryBuilder {
        $query = new fQueryBuilder($this, '');
        return $query->select($columns);
    }
    
    public function raw(string $expression): fRawExpression {
        return new fRawExpression($expression);
    }
    
    // Mock methods that would normally interact with database
    public function query($sql, ...$params) {
        return new MockResult([]);
    }
    
    public function execute($sql, ...$params) {
        return true;
    }
    
    public function escape($format, ...$params) {
        return sprintf($format, ...$params);
    }
}

class MockResult {
    private $data;
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function fetchRow() {
        return array_shift($this->data);
    }
}

// Load test framework function
if (!function_exists('test')) {
    function test($description, $callable) {
        static $testCount = 0;
        static $passedCount = 0;
        static $failedCount = 0;
        
        $testCount++;
        echo "🔹 Test $testCount: $description\n";
        
        try {
            $result = $callable();
            if ($result === true) {
                $passedCount++;
                echo "✅ PASSED\n\n";
            } else {
                $failedCount++;
                echo "❌ FAILED: $result\n\n";
            }
        } catch (Throwable $e) {
            $failedCount++;
            echo "❌ ERROR: " . $e->getMessage() . "\n\n";
        }
        
        // Summary at the end
        register_shutdown_function(function() use (&$testCount, &$passedCount, &$failedCount) {
            static $summaryShown = false;
            if (!$summaryShown && $testCount > 0) {
                echo "\n📊 SUMMARY\n";
                echo "Total: $testCount, Passed: $passedCount, Failed: $failedCount\n";
                echo "Success Rate: " . round(($passedCount / $testCount) * 100, 1) . "%\n";
                $summaryShown = true;
            }
        });
    }
}

echo "🔧 fQueryBuilder Test Suite (SQL Generation Tests)\n";
echo "============================================================\n\n";

// ===== TEST 1: Basic Query Builder Creation =====
test('Query Builder: Basic instantiation and method chaining', function() use ($classesDir) {
    try {
        $db = new MockfDatabase();
        
        // Test basic table() method
        $query = $db->table('users');
        
        if (!($query instanceof fQueryBuilder)) {
            return "Query builder not returned from table() method";
        }
        
        // Test method chaining
        $chainedQuery = $query->select(['id', 'name', 'email'])
                             ->where('active', '=', 1)
                             ->orderBy('name', 'ASC')
                             ->limit(10);
        
        if (!($chainedQuery instanceof fQueryBuilder)) {
            return "Method chaining failed";
        }
        
        if ($query !== $chainedQuery) {
            return "Method chaining should return same instance";
        }
        
        return true;
        
    } catch (Exception $e) {
        return "Query Builder creation failed: " . $e->getMessage();
    }
});

// ===== TEST 2: Basic SELECT SQL Generation =====
test('Query Builder: Basic SELECT SQL generation', function() use ($classesDir) {
    try {
        $db = new MockfDatabase();
        
        // Test basic SELECT
        $sql = $db->table('users')
                  ->select(['id', 'name', 'email'])
                  ->toSql();
        
        $expected = "SELECT id, name, email FROM users";
        if ($sql !== $expected) {
            return "Basic SELECT failed. Expected: '$expected', Got: '$sql'";
        }
        
        // Test SELECT with WHERE
        $sql = $db->table('users')
                  ->select(['*'])
                  ->where('active', '=', 1)
                  ->toSql();
        
        if (!str_contains($sql, 'WHERE active = %i')) {
            return "WHERE clause not properly generated: $sql";
        }
        
        // Test bindings
        $query = $db->table('users')->where('name', '=', 'John');
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        if (count($bindings) !== 1 || $bindings[0] !== 'John') {
            return "Parameter bindings failed";
        }
        
        return true;
        
    } catch (Exception $e) {
        return "SELECT SQL generation failed: " . $e->getMessage();
    }
});

// ===== TEST 3: Advanced WHERE Clauses =====
test('Query Builder: Advanced WHERE clause types', function() use ($classesDir) {
    try {
        $db = new MockfDatabase();
        
        // Test whereIn
        $sql = $db->table('users')
                  ->whereIn('id', [1, 2, 3])
                  ->toSql();
        
        if (!str_contains($sql, 'WHERE id IN (%i, %i, %i)')) {
            return "whereIn clause failed: $sql";
        }
        
        // Test whereBetween
        $sql = $db->table('products')
                  ->whereBetween('price', 10, 100)
                  ->toSql();
        
        if (!str_contains($sql, 'WHERE price BETWEEN %i AND %i')) {
            return "whereBetween clause failed: $sql";
        }
        
        // Test whereNull
        $sql = $db->table('users')
                  ->whereNull('deleted_at')
                  ->toSql();
        
        if (!str_contains($sql, 'WHERE deleted_at IS NULL')) {
            return "whereNull clause failed: $sql";
        }
        
        // Test whereLike
        $sql = $db->table('users')
                  ->whereLike('name', '%John%')
                  ->toSql();
        
        if (!str_contains($sql, 'WHERE name LIKE %s')) {
            return "whereLike clause failed: $sql";
        }
        
        // Test multiple WHERE conditions with AND/OR
        $sql = $db->table('users')
                  ->where('active', '=', 1)
                  ->orWhere('admin', '=', 1)
                  ->toSql();
        
        if (!str_contains($sql, 'WHERE active = %i OR admin = %i')) {
            return "Multiple WHERE with OR failed: $sql";
        }
        
        return true;
        
    } catch (Exception $e) {
        return "Advanced WHERE clauses failed: " . $e->getMessage();
    }
});

// ===== TEST 4: JOIN Operations =====
test('Query Builder: JOIN operations', function() use ($classesDir) {
    try {
        $db = new MockfDatabase();
        
        // Test basic INNER JOIN
        $sql = $db->table('users')
                  ->join('profiles', 'users.id', '=', 'profiles.user_id')
                  ->select(['users.name', 'profiles.bio'])
                  ->toSql();
        
        if (!str_contains($sql, 'INNER JOIN profiles ON users.id = profiles.user_id')) {
            return "Basic INNER JOIN failed: $sql";
        }
        
        // Test LEFT JOIN
        $sql = $db->table('users')
                  ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
                  ->toSql();
        
        if (!str_contains($sql, 'LEFT JOIN orders ON users.id = orders.user_id')) {
            return "LEFT JOIN failed: $sql";
        }
        
        // Test RIGHT JOIN
        $sql = $db->table('orders')
                  ->rightJoin('users', 'orders.user_id', '=', 'users.id')
                  ->toSql();
        
        if (!str_contains($sql, 'RIGHT JOIN users ON orders.user_id = users.id')) {
            return "RIGHT JOIN failed: $sql";
        }
        
        // Test CROSS JOIN
        $sql = $db->table('categories')
                  ->crossJoin('tags')
                  ->toSql();
        
        if (!str_contains($sql, 'CROSS JOIN tags')) {
            return "CROSS JOIN failed: $sql";
        }
        
        // Test multiple JOINs
        $sql = $db->table('users')
                  ->join('profiles', 'users.id', '=', 'profiles.user_id')
                  ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
                  ->toSql();
        
        $hasInnerJoin = str_contains($sql, 'INNER JOIN profiles');
        $hasLeftJoin = str_contains($sql, 'LEFT JOIN orders');
        
        if (!$hasInnerJoin || !$hasLeftJoin) {
            return "Multiple JOINs failed: $sql";
        }
        
        return true;
        
    } catch (Exception $e) {
        return "JOIN operations failed: " . $e->getMessage();
    }
});

// ===== TEST 5: INSERT Operations =====
test('Query Builder: INSERT operations', function() use ($classesDir) {
    try {
        $db = new MockfDatabase();
        
        // Test basic INSERT
        $query = $db->table('users')->insert([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30
        ]);
        
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        if (!str_contains($sql, 'INSERT INTO users')) {
            return "INSERT statement not generated: $sql";
        }
        
        if (!str_contains($sql, '(name, email, age)')) {
            return "Column names not included: $sql";
        }
        
        if (!str_contains($sql, 'VALUES (%s, %s, %i)')) {
            return "VALUES placeholders incorrect: $sql";
        }
        
        if (count($bindings) !== 3) {
            return "Binding count incorrect: " . count($bindings);
        }
        
        if ($bindings[0] !== 'John Doe' || $bindings[1] !== 'john@example.com' || $bindings[2] !== 30) {
            return "Binding values incorrect";
        }
        
        return true;
        
    } catch (Exception $e) {
        return "INSERT operations failed: " . $e->getMessage();
    }
});

// ===== TEST 6: UPDATE Operations =====
test('Query Builder: UPDATE operations', function() use ($classesDir) {
    try {
        $db = new MockfDatabase();
        
        // Test basic UPDATE
        $query = $db->table('users')
                    ->where('id', '=', 1)
                    ->update([
                        'name' => 'Jane Doe',
                        'email' => 'jane@example.com'
                    ]);
        
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        if (!str_contains($sql, 'UPDATE users SET')) {
            return "UPDATE statement not generated: $sql";
        }
        
        if (!str_contains($sql, 'name = %s')) {
            return "SET clause not properly generated: $sql";
        }
        
        if (!str_contains($sql, 'WHERE id = %i')) {
            return "WHERE clause not included in UPDATE: $sql";
        }
        
        if (count($bindings) !== 3) { // 2 for SET, 1 for WHERE
            return "UPDATE binding count incorrect: " . count($bindings);
        }
        
        return true;
        
    } catch (Exception $e) {
        return "UPDATE operations failed: " . $e->getMessage();
    }
});

// ===== TEST 7: DELETE Operations =====
test('Query Builder: DELETE operations', function() use ($classesDir) {
    try {
        $db = new MockfDatabase();
        
        // Test basic DELETE
        $query = $db->table('users')
                    ->where('active', '=', 0)
                    ->delete();
        
        $sql = $query->toSql();
        
        if (!str_contains($sql, 'DELETE FROM users')) {
            return "DELETE statement not generated: $sql";
        }
        
        if (!str_contains($sql, 'WHERE active = %i')) {
            return "WHERE clause not included in DELETE: $sql";
        }
        
        return true;
        
    } catch (Exception $e) {
        return "DELETE operations failed: " . $e->getMessage();
    }
});

// ===== TEST 8: Raw Expressions =====
test('Query Builder: Raw SQL expressions', function() use ($classesDir) {
    try {
        $db = new MockfDatabase();
        
        // Test fRawExpression creation
        $raw = $db->raw('COUNT(*)');
        
        if (!($raw instanceof fRawExpression)) {
            return "Raw expression not created correctly";
        }
        
        if ($raw->getValue() !== 'COUNT(*)') {
            return "Raw expression value incorrect";
        }
        
        // Test raw in SELECT
        $sql = $db->table('users')
                  ->select([$db->raw('COUNT(*) as user_count')])
                  ->toSql();
        
        if (!str_contains($sql, 'COUNT(*) as user_count')) {
            return "Raw expression in SELECT failed: $sql";
        }
        
        // Test whereRaw
        $sql = $db->table('users')
                  ->whereRaw('YEAR(created_at) = YEAR(NOW())')
                  ->toSql();
        
        if (!str_contains($sql, 'WHERE YEAR(created_at) = YEAR(NOW())')) {
            return "whereRaw failed: $sql";
        }
        
        return true;
        
    } catch (Exception $e) {
        return "Raw expressions failed: " . $e->getMessage();
    }
});

// ===== TEST 9: Static Facade (fDB) =====
test('Query Builder: Static fDB facade', function() use ($classesDir) {
    try {
        // Create a test database and set it
        $db = new MockfDatabase();
        fDB::setDatabase($db);
        
        // Test that fDB returns the same database
        $retrievedDb = fDB::getDatabase();
        if ($retrievedDb !== $db) {
            return "fDB::getDatabase() doesn't return same instance";
        }
        
        // Test static table method
        $query = fDB::table('users');
        if (!($query instanceof fQueryBuilder)) {
            return "fDB::table() doesn't return fQueryBuilder instance";
        }
        
        // Test static select method
        $query = fDB::select(['name', 'email']);
        if (!($query instanceof fQueryBuilder)) {
            return "fDB::select() doesn't return fQueryBuilder instance";
        }
        
        // Test static convenience methods
        $query = fDB::selectFrom('users', ['id', 'name']);
        $sql = $query->toSql();
        
        if (!str_contains($sql, 'SELECT id, name FROM users')) {
            return "fDB::selectFrom() failed: $sql";
        }
        
        // Test static raw method
        $raw = fDB::raw('NOW()');
        if (!($raw instanceof fRawExpression)) {
            return "fDB::raw() doesn't return fRawExpression";
        }
        
        return true;
        
    } catch (Exception $e) {
        return "Static fDB facade failed: " . $e->getMessage();
    }
});

// ===== TEST 10: Complex Query Building =====
test('Query Builder: Complex query with multiple clauses', function() use ($classesDir) {
    try {
        $db = new MockfDatabase();
        
        // Build a complex query with multiple clauses
        $query = $db->table('users')
                    ->select(['users.name', 'profiles.bio', 'COUNT(orders.id) as order_count'])
                    ->join('profiles', 'users.id', '=', 'profiles.user_id')
                    ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
                    ->where('users.active', '=', 1)
                    ->where('users.created_at', '>', '2023-01-01')
                    ->orWhere('users.admin', '=', 1)
                    ->whereNotNull('profiles.bio')
                    ->groupBy('users.id')
                    ->having('order_count', '>', 0)
                    ->orderBy('users.name', 'ASC')
                    ->limit(50)
                    ->offset(10);
        
        $sql = $query->toSql();
        
        // Verify main components are present
        $requiredParts = [
            'SELECT',
            'FROM users',
            'INNER JOIN profiles',
            'LEFT JOIN orders',
            'WHERE',
            'GROUP BY',
            'HAVING',
            'ORDER BY',
            'LIMIT',
            'OFFSET'
        ];
        
        foreach ($requiredParts as $part) {
            if (!str_contains($sql, $part)) {
                return "Complex query missing: $part. SQL: $sql";
            }
        }
        
        // Verify bindings are correctly collected
        $bindings = $query->getBindings();
        if (count($bindings) < 4) { // At least 4 bindings expected
            return "Complex query bindings insufficient: " . count($bindings);
        }
        
        return true;
        
    } catch (Exception $e) {
        return "Complex query building failed: " . $e->getMessage();
    }
});

// ===== TEST 11: Parameter Type Detection =====
test('Query Builder: Parameter type detection and placeholders', function() use ($classesDir) {
    try {
        $db = new MockfDatabase();
        
        // Test different parameter types
        $query = $db->table('mixed_types')
                    ->insert([
                        'string_col' => 'text value',
                        'int_col' => 42,
                        'float_col' => 3.14,
                        'bool_col' => true,
                        'null_col' => null
                    ]);
        
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        // Verify correct placeholders are used
        if (!str_contains($sql, '%s')) { // String placeholder
            return "String placeholder (%s) not found: $sql";
        }
        
        if (!str_contains($sql, '%i')) { // Integer placeholder
            return "Integer placeholder (%i) not found: $sql";
        }
        
        if (!str_contains($sql, '%f')) { // Float placeholder
            return "Float placeholder (%f) not found: $sql";
        }
        
        if (!str_contains($sql, '%b')) { // Boolean placeholder
            return "Boolean placeholder (%b) not found: $sql";
        }
        
        if (!str_contains($sql, 'NULL')) { // NULL literal
            return "NULL literal not found: $sql";
        }
        
        // Verify bindings contain correct values (NULL gets bound as well in our implementation)
        if (count($bindings) !== 5) { // All values including NULL get bound
            return "Binding count incorrect: " . count($bindings) . ". Expected 5, bindings: " . json_encode($bindings);
        }
        
        return true;
        
    } catch (Exception $e) {
        return "Parameter type detection failed: " . $e->getMessage();
    }
});

// ===== TEST 12: SQL Injection Protection =====
test('Query Builder: SQL injection protection', function() use ($classesDir) {
    try {
        $db = new MockfDatabase();
        
        // Test malicious input is properly bound as parameter
        $maliciousInput = "'; DROP TABLE users; --";
        
        $query = $db->table('users')
                    ->where('name', '=', $maliciousInput);
        
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        // SQL should contain placeholder, not the raw malicious input
        if (str_contains($sql, 'DROP TABLE')) {
            return "SQL injection vulnerability: raw malicious input in SQL: $sql";
        }
        
        if (!str_contains($sql, 'WHERE name = %s')) {
            return "Parameter placeholder not generated: $sql";
        }
        
        // Malicious input should be in bindings array
        if (!in_array($maliciousInput, $bindings)) {
            return "Malicious input not properly bound";
        }
        
        return true;
        
    } catch (Exception $e) {
        return "SQL injection protection failed: " . $e->getMessage();
    }
});

echo "\n============================================================\n";
echo "🎯 Query Builder Test Suite Complete\n";