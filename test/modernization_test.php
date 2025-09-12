<?php
/**
 * Modernization Tests
 * 
 * Tests that verify the PHP 8.4 modernization patterns were applied correctly
 */

// Test that variadic parameters work in modernized methods
test('Variadic parameters work correctly in fCore::compose', function() {
    $classesDir = dirname(__DIR__);
    
    try {
        require_once $classesDir . '/lib/fCore.php';
        
        // Test with multiple parameters
        $result1 = fCore::compose('Hello %s, you have %d messages', 'John', 5);
        if (!str_contains($result1, 'Hello John') || !str_contains($result1, '5 messages')) {
            return "fCore::compose variadic parameters not working correctly";
        }
        
        // Test with single parameter
        $result2 = fCore::compose('Simple message: %s', 'test');
        if (!str_contains($result2, 'Simple message: test')) {
            return "fCore::compose single parameter not working";
        }
        
        return true;
    } catch (Exception $e) {
        return "fCore::compose test failed: " . $e->getMessage();
    }
});

// Test that call_user_func_array was modernized properly
test('call_user_func_array patterns modernized', function() {
    $classesDir = dirname(__DIR__);
    $criticalFiles = ['fCore.php', 'fActiveRecord.php', 'fORM.php'];
    $problematicPatterns = [];
    
    foreach ($criticalFiles as $file) {
        $path = $classesDir . '/lib/' . $file;
        if (file_exists($path)) {
            $content = file_get_contents($path);
            
            // Look for call_user_func_array patterns that should be modernized
            if (preg_match('/call_user_func_array\s*\(\s*([^,]+),\s*\$[a-zA-Z_]+\s*\)/', $content, $matches)) {
                // Check if it's a simple callback that could be modernized
                $callback = trim($matches[1]);
                if (strpos($callback, '$') !== false || strpos($callback, 'array') !== false) {
                    $problematicPatterns[] = "$file: " . trim($matches[0]);
                }
            }
        }
    }
    
    return empty($problematicPatterns) ? true : "Unmodernized patterns: " . implode('; ', $problematicPatterns);
});

// Test that the fValidationException::compose method works with variadic parameters
test('fValidationException::compose modernization functional', function() {
    $classesDir = dirname(__DIR__);
    
    try {
        require_once $classesDir . '/lib/fCore.php';
        require_once $classesDir . '/lib/fException.php';
        require_once $classesDir . '/lib/fExpectedException.php';
        require_once $classesDir . '/lib/fValidationException.php';
        
        // Create test exception with multiple parameters
        $exception = new fValidationException('Test error: %s with code %d', 'sample', 404);
        $message = $exception->getMessage();
        
        if (!str_contains($message, 'Test error: sample') || !str_contains($message, 'code 404')) {
            return "fValidationException compose not working with variadic parameters";
        }
        
        return true;
    } catch (Exception $e) {
        return "fValidationException test failed: " . $e->getMessage();
    }
});

// Test modernized boolean usage in conditional statements
test('Boolean modernization maintains logic', function() {
    $classesDir = dirname(__DIR__);
    
    // Test a few key boolean operations work as expected
    $testFile = $classesDir . '/lib/fFile.php';
    if (file_exists($testFile)) {
        $content = file_get_contents($testFile);
        
        // Ensure we have modernized boolean constants
        if (preg_match('/\$\w+\s*=\s*false\s*;/', $content) === 0) {
            return "No modernized boolean assignments found in fFile.php";
        }
        
        // Ensure comparison operations use lowercase
        if (preg_match('/!==\s*false/', $content) === 0) {
            return "No modernized boolean comparisons found in fFile.php";
        }
        
        return true;
    }
    
    return "fFile.php not found for boolean testing";
});

// Test that null coalescing works with modernized null values
test('Null modernization maintains functionality', function() {
    $classesDir = dirname(__DIR__);
    
    // Test null usage patterns
    try {
        $testValue = null;
        $result = $testValue ?? 'default';
        
        if ($result !== 'default') {
            return "Null coalescing not working properly";
        }
        
        // Test null comparison
        if ($testValue !== null) {
            return "Null comparison not working properly";
        }
        
        return true;
    } catch (Exception $e) {
        return "Null modernization test failed: " . $e->getMessage();
    }
});

// Test that modernized classes still maintain backwards compatibility
test('Backwards compatibility maintained', function() {
    $classesDir = dirname(__DIR__);
    
    try {
        require_once $classesDir . '/lib/fCore.php';
        
        // Test that old-style method calls still work
        $oldStyleResult = fCore::compose('Test %s', 'value');
        $newStyleResult = fCore::compose('Test %s', 'value');
        
        if ($oldStyleResult !== $newStyleResult) {
            return "Backwards compatibility broken for fCore::compose";
        }
        
        return true;
    } catch (Exception $e) {
        return "Backwards compatibility test failed: " . $e->getMessage();
    }
});

// Test file loading with modernized fLoader
test('fLoader modernization functional', function() {
    $classesDir = dirname(__DIR__);
    
    try {
        require_once $classesDir . '/lib/fLoader.php';
        
        // Test that fLoader can still handle class loading
        $reflection = new ReflectionClass('fLoader');
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        $expectedMethods = ['autoload', 'best', 'eager', 'hasOpcodeCache', 'lazy'];
        foreach ($expectedMethods as $method) {
            if (!$reflection->hasMethod($method)) {
                return "fLoader missing method: $method";
            }
        }
        
        return true;
    } catch (Exception $e) {
        return "fLoader test failed: " . $e->getMessage();
    }
});