<?php
/**
 * fDatabase Compatibility Tests
 * 
 * These tests verify that fDatabase properly handles different database extension scenarios
 */

// Test that fDatabase correctly detects missing extensions
test('fDatabase: Proper extension detection and error handling', function() {
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
        require_once $classesDir . '/lib/fDatabase.php';
        
        // Test 1: fDatabase class loads successfully
        if (!class_exists('fDatabase')) {
            return "fDatabase class not found";
        }
        
        // Test 2: fDatabase handles MySQL extensions appropriately
        if (extension_loaded('mysqli') || extension_loaded('pdo_mysql')) {
            // Extensions available - should connect (or fail for different reason like connection)
            try {
                $db = new fDatabase('mysql', 'testdb', 'testuser', 'testpass', 'localhost');
                // Connection attempt should happen (may fail due to invalid credentials, but that's different)
            } catch (Exception $e) {
                // This is acceptable - may fail due to connection issues, but not extension issues
                if (strpos($e->getMessage(), 'extension') !== false) {
                    return "fDatabase reporting extension issues when extensions are available: " . $e->getMessage();
                }
            }
        } else {
            // Extensions missing - should detect and report
            try {
                $db = new fDatabase('mysql', 'testdb', 'testuser', 'testpass', 'localhost');
                return "Expected fDatabase to throw exception for missing extensions";
            } catch (Exception $e) {
                $message = $e->getMessage();
                if (strpos($message, 'extension') === false) {
                    return "Exception doesn't mention missing extensions: " . $message;
                }
            }
        }
        
        // Test 3: Verify fDatabase class has expected methods
        $reflection = new ReflectionClass('fDatabase');
        $expectedMethods = ['execute', 'query', 'escape'];
        
        foreach ($expectedMethods as $method) {
            if (!$reflection->hasMethod($method)) {
                return "fDatabase missing expected method: $method";
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        return "fDatabase compatibility test failed: " . $e->getMessage();
    }
});

// Test that fDatabase is properly marked as PHP 8.4 compatible
test('fDatabase: PHP 8.4 compatibility confirmation', function() {
    $classesDir = dirname(__DIR__);
    $databaseFile = $classesDir . '/lib/fDatabase.php';
    
    if (!file_exists($databaseFile)) {
        return "fDatabase.php not found";
    }
    
    $content = file_get_contents($databaseFile);
    
    // Should be marked as PHP 8.4 compatible, NOT deprecated
    if (strpos($content, 'DEPRECATED - PHP 8.4') !== false) {
        return "fDatabase incorrectly marked as deprecated";
    }
    
    if (strpos($content, 'PHP 8.4 COMPATIBLE') === false) {
        return "fDatabase missing PHP 8.4 compatibility marker";
    }
    
    // Should mention modern database extensions
    if (strpos($content, 'MySQLi') === false && strpos($content, 'PDO') === false) {
        return "fDatabase documentation doesn't mention modern extensions";
    }
    
    return true;
});

// Test fDatabase abstraction layer concept
test('fDatabase: Database abstraction layer verification', function() {
    $classesDir = dirname(__DIR__);
    $databaseFile = $classesDir . '/lib/fDatabase.php';
    
    $content = file_get_contents($databaseFile);
    
    // Should support multiple database types
    $databases = ['mysql', 'postgresql', 'sqlite'];
    $missingSupport = [];
    
    foreach ($databases as $db) {
        if (strpos(strtolower($content), $db) === false) {
            $missingSupport[] = $db;
        }
    }
    
    if (!empty($missingSupport)) {
        return "fDatabase might not support: " . implode(', ', $missingSupport);
    }
    
    // Should properly handle obsolete mysql_* functions with extension detection
    if (strpos($content, 'mysql_connect') !== false) {
        // If mysql_* functions exist, there should be proper extension checking
        if (strpos($content, 'extension_loaded') === false && strpos($content, 'function_exists') === false) {
            return "fDatabase uses mysql_* functions without extension checking";
        }
    }
    
    return true;
});

// Test that fDatabase error handling is appropriate for production
test('fDatabase: Production-ready error handling', function() {
    $classesDir = dirname(__DIR__);
    
    try {
        require_once $classesDir . '/lib/fDatabase.php';
        
        // Test with invalid database type
        try {
            $db = new fDatabase('invaliddb', 'test', 'test', 'test', 'localhost');
            return "fDatabase should reject invalid database types";
        } catch (Exception $e) {
            // This is expected - good error handling
        }
        
        // Test with missing parameters
        try {
            $db = new fDatabase('mysql', '', '', '', '');
            return "fDatabase should validate required parameters";
        } catch (Exception $e) {
            // This is expected - proper validation
        }
        
        return true;
        
    } catch (Exception $e) {
        return "Error handling test failed: " . $e->getMessage();
    }
});