<?php
/**
 * fDatabase Real World Tests
 * 
 * These tests demonstrate that fDatabase works correctly with modern database connections
 */

// Database configuration for real testing
// NOTE: Replace with your own database credentials or use environment variables
$db_config = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'database' => getenv('DB_DATABASE') ?: 'flourish_test', 
    'username' => getenv('DB_USERNAME') ?: 'flourish_user',
    'password' => getenv('DB_PASSWORD') ?: 'your_secure_password_here'
];

// Skip real database tests if no credentials are provided
if ($db_config['password'] === 'your_secure_password_here') {
    echo "⚠️  Skipping real database tests - Please configure database credentials via environment variables:\n";
    echo "   DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD\n";
    return;
}

// ===== REAL fDatabase TEST 1: Connection and Basic Operations =====
test('fDatabase: Real Connection and CRUD Operations', function() use ($db_config) {
    $classesDir = dirname(__DIR__);
    
    try {
        // Load required classes
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
        
        // Test 1: Create fDatabase connection (this should work with MySQLi/PDO)
        $dsn = sprintf(
            'mysql://%s:%s@%s/%s',
            $db_config['username'],
            $db_config['password'], 
            $db_config['host'],
            $db_config['database']
        );
        
        $db = new fDatabase('mysql', $db_config['database'], $db_config['username'], $db_config['password'], $db_config['host']);
        
        // Test 2: Create a test table
        $create_table_sql = "
            CREATE TABLE IF NOT EXISTS flourish_test_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        $db->execute($create_table_sql);
        
        // Test 3: Insert test data
        $insert_sql = "INSERT INTO flourish_test_users (name, email) VALUES (%s, %s)";
        
        $test_users = [
            ['Alice Johnson', 'alice@flourishtest.com'],
            ['Bob Smith', 'bob@flourishtest.com'], 
            ['Carol Wilson', 'carol@flourishtest.com']
        ];
        
        foreach ($test_users as $user) {
            $db->execute($insert_sql, $user[0], $user[1]);
        }
        
        // Test 4: Query data
        $select_sql = "SELECT * FROM flourish_test_users ORDER BY name";
        $result = $db->query($select_sql);
        
        $users = [];
        foreach ($result as $row) {
            $users[] = $row;
        }
        
        if (count($users) < 3) {
            return "Expected at least 3 users, found: " . count($users);
        }
        
        // Verify first user
        $first_user = $users[0];
        if ($first_user['name'] !== 'Alice Johnson') {
            return "First user name incorrect: " . $first_user['name'];
        }
        
        // Test 5: Update operation
        $update_sql = "UPDATE flourish_test_users SET name = %s WHERE email = %s";
        $db->execute($update_sql, 'Alice Smith-Johnson', 'alice@flourishtest.com');
        
        // Verify update
        $check_sql = "SELECT name FROM flourish_test_users WHERE email = %s";
        $updated_result = $db->query($check_sql, 'alice@flourishtest.com');
        $updated_user = $updated_result->fetchRow();
        
        if ($updated_user['name'] !== 'Alice Smith-Johnson') {
            return "User update failed: " . $updated_user['name'];
        }
        
        // Test 6: Count operation
        $count_sql = "SELECT COUNT(*) as user_count FROM flourish_test_users";
        $count_result = $db->query($count_sql);
        $count_row = $count_result->fetchRow();
        
        if ((int)$count_row['user_count'] < 3) {
            return "User count incorrect: " . $count_row['user_count'];
        }
        
        // Test 7: Delete operation
        $delete_sql = "DELETE FROM flourish_test_users WHERE email LIKE %s";
        $db->execute($delete_sql, '%@flourishtest.com');
        
        // Verify deletion
        $final_count_result = $db->query($count_sql);
        $final_count_row = $final_count_result->fetchRow();
        
        if ((int)$final_count_row['user_count'] !== 0) {
            return "Users not properly deleted: " . $final_count_row['user_count'];
        }
        
        // Clean up table
        $db->execute("DROP TABLE IF EXISTS flourish_test_users");
        
        return true;
        
    } catch (Exception $e) {
        return "fDatabase real test failed: " . $e->getMessage();
    }
});

// ===== REAL fDatabase TEST 2: Transaction Support =====
test('fDatabase: Transaction Management', function() use ($db_config) {
    $classesDir = dirname(__DIR__);
    
    try {
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
        
        // Create database connection
        $db = new fDatabase('mysql', $db_config['database'], $db_config['username'], $db_config['password'], $db_config['host']);
        
        // Create test table
        $db->execute("
            CREATE TABLE IF NOT EXISTS flourish_transaction_test (
                id INT AUTO_INCREMENT PRIMARY KEY,
                amount DECIMAL(10,2) NOT NULL,
                description VARCHAR(255) NOT NULL
            )
        ");
        
        // Test 1: Successful transaction
        $db->execute("BEGIN");
        
        $db->execute("INSERT INTO flourish_transaction_test (amount, description) VALUES (%f, %s)", 100.50, 'Initial deposit');
        $db->execute("INSERT INTO flourish_transaction_test (amount, description) VALUES (%f, %s)", -25.00, 'Purchase');
        $db->execute("INSERT INTO flourish_transaction_test (amount, description) VALUES (%f, %s)", 50.00, 'Bonus');
        
        $db->execute("COMMIT");
        
        // Verify transaction success
        $result = $db->query("SELECT COUNT(*) as count FROM flourish_transaction_test");
        $count_row = $result->fetchRow();
        
        if ((int)$count_row['count'] !== 3) {
            return "Transaction commit failed, expected 3 rows, found: " . $count_row['count'];
        }
        
        // Test 2: Transaction rollback
        $db->execute("BEGIN");
        
        $db->execute("INSERT INTO flourish_transaction_test (amount, description) VALUES (%f, %s)", 1000.00, 'Should be rolled back');
        
        // Check that row exists before rollback
        $temp_result = $db->query("SELECT COUNT(*) as count FROM flourish_transaction_test");
        $temp_count = $temp_result->fetchRow();
        
        if ((int)$temp_count['count'] !== 4) {
            return "Row not inserted before rollback";
        }
        
        $db->execute("ROLLBACK");
        
        // Verify rollback worked
        $final_result = $db->query("SELECT COUNT(*) as count FROM flourish_transaction_test");
        $final_count = $final_result->fetchRow();
        
        if ((int)$final_count['count'] !== 3) {
            return "Transaction rollback failed, expected 3 rows, found: " . $final_count['count'];
        }
        
        // Test 3: Calculate balance (test aggregate functions)
        $balance_result = $db->query("SELECT SUM(amount) as balance FROM flourish_transaction_test");
        $balance_row = $balance_result->fetchRow();
        $balance = (float)$balance_row['balance'];
        
        // Should be 100.50 - 25.00 + 50.00 = 125.50
        if (abs($balance - 125.50) > 0.01) {
            return "Balance calculation failed: expected 125.50, got $balance";
        }
        
        // Clean up
        $db->execute("DROP TABLE IF EXISTS flourish_transaction_test");
        
        return true;
        
    } catch (Exception $e) {
        return "fDatabase transaction test failed: " . $e->getMessage();
    }
});

// ===== REAL fDatabase TEST 3: Prepared Statements and Security =====
test('fDatabase: Prepared Statements and SQL Injection Protection', function() use ($db_config) {
    $classesDir = dirname(__DIR__);
    
    try {
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
        
        $db = new fDatabase('mysql', $db_config['database'], $db_config['username'], $db_config['password'], $db_config['host']);
        
        // Create test table
        $db->execute("
            CREATE TABLE IF NOT EXISTS flourish_security_test (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                email VARCHAR(100) NOT NULL
            )
        ");
        
        // Test 1: Safe parameter binding (should work)
        $safe_username = "john_doe";
        $safe_email = "john@example.com"; 
        $safe_password = password_hash("secure_password", PASSWORD_DEFAULT);
        
        $db->execute(
            "INSERT INTO flourish_security_test (username, password_hash, email) VALUES (%s, %s, %s)",
            $safe_username,
            $safe_password,
            $safe_email
        );
        
        // Verify insertion
        $result = $db->query("SELECT * FROM flourish_security_test WHERE username = %s", $safe_username);
        $user = $result->fetchRow();
        
        if (!$user || $user['username'] !== $safe_username) {
            return "Safe parameter binding failed";
        }
        
        // Test 2: SQL injection attempt (should be safely handled)
        $malicious_input = "'; DROP TABLE flourish_security_test; --";
        
        // This should safely escape the malicious input
        $injection_result = $db->query("SELECT * FROM flourish_security_test WHERE username = %s", $malicious_input);
        $injection_rows = [];
        foreach ($injection_result as $row) {
            $injection_rows[] = $row;
        }
        
        // Should return no results (input treated as literal string)
        if (count($injection_rows) > 0) {
            return "Potential SQL injection vulnerability detected";
        }
        
        // Test 3: Verify table still exists (wasn't dropped by injection)
        $count_result = $db->query("SELECT COUNT(*) as count FROM flourish_security_test");
        $count_row = $count_result->fetchRow();
        
        if ((int)$count_row['count'] !== 1) {
            return "Table may have been compromised by SQL injection";
        }
        
        // Test 4: Different data types parameter binding
        $db->execute("
            CREATE TEMPORARY TABLE flourish_types_test (
                id INT AUTO_INCREMENT PRIMARY KEY,
                string_val VARCHAR(100),
                int_val INT,
                float_val DECIMAL(10,2),
                date_val DATE
            )
        ");
        
        $db->execute(
            "INSERT INTO flourish_types_test (string_val, int_val, float_val, date_val) VALUES (%s, %i, %f, %s)",
            "Test String",
            42,
            123.45,
            "2024-01-15"
        );
        
        $types_result = $db->query("SELECT * FROM flourish_types_test");
        $types_row = $types_result->fetchRow();
        
        if ($types_row['string_val'] !== 'Test String') {
            return "String parameter binding failed";
        }
        
        if ((int)$types_row['int_val'] !== 42) {
            return "Integer parameter binding failed";
        }
        
        if (abs((float)$types_row['float_val'] - 123.45) > 0.01) {
            return "Float parameter binding failed";
        }
        
        // Clean up
        $db->execute("DROP TABLE IF EXISTS flourish_security_test");
        
        return true;
        
    } catch (Exception $e) {
        return "fDatabase security test failed: " . $e->getMessage();
    }
});