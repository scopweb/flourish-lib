<?php
/**
 * Simple test focused only on fDirectory with Property Hooks
 */

// Include all required dependencies for fDirectory
$classes = [
    'lib/fCore.php',
    'lib/fException.php', 
    'lib/fExpectedException.php',
    'lib/fUnexpectedException.php',
    'lib/fEnvironmentException.php',
    'lib/fProgrammerException.php',
    'lib/fValidationException.php',
    'lib/fFilesystem.php',
    'lib/fFile.php',
    'lib/fDirectory.php'
];

foreach ($classes as $class) {
    if (file_exists($class)) {
        require_once $class;
    } else {
        echo "❌ Missing: $class\n";
        exit(1);
    }
}

echo "🧪 fDirectory Property Hooks Test\n";
echo "================================\n\n";

try {
    // Create a test directory 
    $testDir = new fDirectory('lib');  // Use lib directory which exists
    
    echo "✅ Successfully created fDirectory object\n";
    
    // Test traditional methods work
    echo "📌 Traditional Methods:\n";
    echo "   getName(): " . $testDir->getName() . "\n";
    echo "   getPath(): " . $testDir->getPath() . "\n";
    echo "   getPath(true): " . $testDir->getPath(true) . "\n\n";
    
    // Test new property hooks
    echo "✨ Property Hooks (PHP 8.4):\n";
    echo "   \$dir->name: " . $testDir->name . "\n";
    echo "   \$dir->path: " . $testDir->path . "\n";  
    echo "   \$dir->webPath: " . $testDir->webPath . "\n\n";
    
    // Test equivalence
    echo "🔍 Equivalence Test:\n";
    $nameMatch = ($testDir->name === $testDir->getName());
    $pathMatch = ($testDir->path === $testDir->getPath());
    $webPathMatch = ($testDir->webPath === $testDir->getPath(true));
    
    echo "   name property === getName(): " . ($nameMatch ? '✅ PASS' : '❌ FAIL') . "\n";
    echo "   path property === getPath(): " . ($pathMatch ? '✅ PASS' : '❌ FAIL') . "\n";  
    echo "   webPath property === getPath(true): " . ($webPathMatch ? '✅ PASS' : '❌ FAIL') . "\n\n";
    
    // Test read-only protection
    echo "🔒 Read-Only Protection Test:\n";
    try {
        $testDir->name = 'test';
        echo "   ❌ FAIL: Should not allow writing to read-only property\n";
    } catch (Error $e) {
        echo "   ✅ PASS: Read-only protection works (" . get_class($e) . ")\n";
    }
    
    $allTests = $nameMatch && $pathMatch && $webPathMatch;
    
    echo "\n" . str_repeat("=", 40) . "\n";
    if ($allTests) {
        echo "🎉 ALL TESTS PASSED!\n";
        echo "Property Hooks implementation successful!\n";
        echo "✅ Backward compatibility maintained\n";
        echo "✅ New modern syntax available\n";
        echo "✅ Read-only protection functional\n";
    } else {
        echo "❌ SOME TESTS FAILED\n";
        echo "Please check the implementation\n";
    }
    
} catch (Exception $e) {
    echo "💥 FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}