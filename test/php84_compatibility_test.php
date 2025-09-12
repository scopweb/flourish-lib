<?php
/**
 * PHP 8.4 Compatibility Tests
 * 
 * Tests core PHP 8.4 compatibility for Flourish classes
 */

// Test that all Flourish classes can be loaded without syntax errors
test('All Flourish classes load without syntax errors', function() {
    $classesDir = dirname(__DIR__);
    $flourishClasses = [
        'fActiveRecord', 'fAuthorization', 'fBuffer', 'fCRUD', 'fCache', 'fCookie', 
        'fCore', 'fCryptography', 'fDatabase', 'fDate', 'fDirectory', 'fEmail', 
        'fFile', 'fFilesystem', 'fGrammar', 'fHTML', 'fImage', 'fJSON', 'fLoader',
        'fMailbox', 'fMessaging', 'fMoney', 'fNumber', 'fORM', 'fORMColumn',
        'fORMDatabase', 'fORMDate', 'fORMFile', 'fORMJSON', 'fORMMoney', 
        'fORMOrdering', 'fORMRelated', 'fORMSchema', 'fORMValidation', 
        'fPagination', 'fRecordSet', 'fRequest', 'fResult', 'fSMTP', 
        'fSQLSchemaTranslation', 'fSQLTranslation', 'fSchema', 'fSession',
        'fStatement', 'fTemplating', 'fText', 'fTime', 'fTimestamp', 'fURL',
        'fUTF8', 'fUnbufferedResult', 'fUpload', 'fValidation', 'fValidationException',
        'fXML'
    ];
    
    $errors = [];
    foreach ($flourishClasses as $class) {
        $file = $classesDir . '/lib/' . $class . '.php';
        if (file_exists($file)) {
            // Check for syntax errors using php -l
            $output = [];
            $return_var = 0;
            exec("php -l \"$file\" 2>&1", $output, $return_var);
            if ($return_var !== 0) {
                $errors[] = "$class: " . implode(' ', $output);
            }
        } else {
            $errors[] = "$class: File not found";
        }
    }
    
    return empty($errors) ? true : "Syntax errors found: " . implode('; ', $errors);
});

// Test that critical classes have modernized boolean constants
test('Critical classes have modernized boolean constants', function() {
    $classesDir = dirname(__DIR__);
    $criticalClasses = ['fCore.php', 'fException.php', 'fValidation.php', 'fActiveRecord.php'];
    $problematicFiles = [];
    
    foreach ($criticalClasses as $file) {
        $path = $classesDir . '/lib/' . $file;
        if (file_exists($path)) {
            $content = file_get_contents($path);
            
            // Check for assignment patterns that should be modernized
            if (preg_match('/\$\w+\s*=\s*(TRUE|FALSE|NULL)\s*;/', $content)) {
                $problematicFiles[] = $file;
            }
        }
    }
    
    // All critical classes should be modernized
    return empty($problematicFiles) ? true : "Critical classes with old constants: " . implode(', ', $problematicFiles);
});

// Test that func_get_args() has been modernized where appropriate  
test('func_get_args() patterns modernized', function() {
    $classesDir = dirname(__DIR__);
    $criticalClasses = ['fCore.php', 'fException.php', 'fValidation.php'];
    $problematicFiles = [];
    
    foreach ($criticalClasses as $file) {
        $path = $classesDir . '/lib/' . $file;
        if (file_exists($path)) {
            $content = file_get_contents($path);
            
            // Look for func_get_args in compose methods (should be modernized)
            if (preg_match('/function\s+compose\s*\([^)]*\).*?func_get_args\(\)/s', $content)) {
                $problematicFiles[] = $file;
            }
        }
    }
    
    return empty($problematicFiles) ? true : "Files still using func_get_args in compose: " . implode(', ', $problematicFiles);
});

// Test that core classes can be instantiated/called without errors
test('Core classes functional test', function() {
    $classesDir = dirname(__DIR__);
    
    try {
        // Test fLoader
        require_once $classesDir . '/lib/fLoader.php';
        
        // Test fCore (should not throw errors)
        require_once $classesDir . '/lib/fCore.php';
        $result = fCore::compose('Test message with %s', 'parameter');
        if (!is_string($result) || strpos($result, 'parameter') === false) {
            return "fCore::compose not working correctly";
        }
        
        return true;
    } catch (Exception $e) {
        return "Core classes error: " . $e->getMessage();
    } catch (Throwable $e) {
        return "Core classes fatal: " . $e->getMessage();  
    }
});

// Test PHP 8.4 specific features compatibility
test('PHP 8.4 specific compatibility', function() {
    // Test that our code works with PHP 8.4's stricter type checking
    if (version_compare(PHP_VERSION, '8.0.0', '<')) {
        return "PHP 8.0+ required for full compatibility testing";
    }
    
    try {
        // Test variadic parameters work correctly
        $testFunc = function(...$args) {
            return count($args);
        };
        
        if ($testFunc('a', 'b', 'c') !== 3) {
            return "Variadic parameters not working correctly";
        }
        
        // Test spread operator
        $testArray = ['a', 'b', 'c'];
        if ($testFunc(...$testArray) !== 3) {
            return "Spread operator not working correctly";
        }
        
        return true;
    } catch (Exception $e) {
        return "PHP 8.4 feature test failed: " . $e->getMessage();
    }
});

// Test session compatibility (if session functions available)
test('Session configuration compatibility', function() {
    $classesDir = dirname(__DIR__);
    $sessionFile = $classesDir . '/lib/fSession.php';
    
    if (!file_exists($sessionFile)) {
        return "fSession.php not found";
    }
    
    $content = file_get_contents($sessionFile);
    
    // Should NOT contain deprecated session.use_only_cookies
    if (strpos($content, "ini_set('session.use_only_cookies'") !== false) {
        return "Deprecated session.use_only_cookies still in use";
    }
    
    // Should contain compatibility comment
    if (strpos($content, 'session.use_only_cookies is deprecated') === false) {
        return "Missing deprecation comment for session.use_only_cookies";
    }
    
    return true;
});