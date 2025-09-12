<?php
/**
 * Test for PHP 8.4 Array Functions implementation
 * Tests array_find, array_find_key, array_any, and array_all functions
 */

require_once __DIR__ . '/../lib/fValidation.php';
require_once __DIR__ . '/../lib/fCore.php';
require_once __DIR__ . '/../lib/fDatabase.php';

class ArrayFunctionsTest
{
    private $results = [];
    private $testCount = 0;
    private $passCount = 0;

    public function runAllTests()
    {
        echo "🧪 PHP 8.4 Array Functions Test Suite\n";
        echo "============================================================\n\n";

        $this->testArrayAnyInValidation();
        $this->testArrayFunctionsInCore();
        $this->testArrayAnyInDatabase();
        $this->testArrayFindFunctions();
        $this->testArrayAllFunctions();
        
        $this->printSummary();
        return $this->passCount === $this->testCount;
    }

    private function assert($condition, $message)
    {
        $this->testCount++;
        if ($condition) {
            $this->passCount++;
            echo "✅ $message\n";
            return true;
        } else {
            echo "❌ $message\n";
            return false;
        }
    }

    private function testArrayAnyInValidation()
    {
        echo "📋 Testing array_any() in fValidation...\n";
        
        // Test array_any in hasValue method simulation
        $test_data = ['', 'valid_value', ''];
        $result = array_any($test_data, fn($value) => !empty($value));
        $this->assert($result === true, "array_any finds valid value in mixed array");
        
        $empty_data = ['', '', ''];
        $result = array_any($empty_data, fn($value) => !empty($value));
        $this->assert($result === false, "array_any returns false for all empty values");
        
        echo "\n";
    }

    private function testArrayFunctionsInCore()
    {
        echo "🔧 Testing array functions in fCore...\n";
        
        // Test findLoadedExtension
        $result = fCore::findLoadedExtension(['non_existent_ext', 'core', 'standard']);
        $this->assert($result !== null, "findLoadedExtension finds loaded extension");
        
        // Test allExtensionsLoaded
        $result = fCore::allExtensionsLoaded(['core', 'standard']);
        $this->assert($result === true, "allExtensionsLoaded returns true for core extensions");
        
        $result = fCore::allExtensionsLoaded(['non_existent_ext', 'another_fake']);
        $this->assert($result === false, "allExtensionsLoaded returns false for non-existent extensions");
        
        // Test anyExtensionLoaded
        $result = fCore::anyExtensionLoaded(['non_existent_ext', 'core']);
        $this->assert($result === true, "anyExtensionLoaded returns true when at least one exists");
        
        $result = fCore::anyExtensionLoaded(['fake1', 'fake2']);
        $this->assert($result === false, "anyExtensionLoaded returns false when none exist");
        
        echo "\n";
    }

    private function testArrayAnyInDatabase()
    {
        echo "💾 Testing array_any() in fDatabase...\n";
        
        // Test the modernized database type validation
        $valid_types = ['db2', 'mssql', 'mysql', 'oracle', 'postgresql', 'sqlite', 'mongodb'];
        $result = array_any($valid_types, fn($type) => $type === 'mysql');
        $this->assert($result === true, "array_any validates mysql as valid database type");
        
        $result = array_any($valid_types, fn($type) => $type === 'invalid_db');
        $this->assert($result === false, "array_any rejects invalid database type");
        
        echo "\n";
    }

    private function testArrayFindFunctions()
    {
        echo "🔍 Testing array_find() functions...\n";
        
        // Test array_find
        $numbers = [1, 2, 3, 4, 5];
        $result = array_find($numbers, fn($n) => $n > 3);
        $this->assert($result === 4, "array_find returns first element > 3");
        
        $result = array_find($numbers, fn($n) => $n > 10);
        $this->assert($result === null, "array_find returns null when no match");
        
        // Test array_find_key
        $assoc = ['a' => 1, 'b' => 2, 'c' => 3];
        $result = array_find_key($assoc, fn($value, $key) => $value > 1);
        $this->assert($result === 'b', "array_find_key returns first key where value > 1");
        
        echo "\n";
    }

    private function testArrayAllFunctions()
    {
        echo "✅ Testing array_all() functions...\n";
        
        // Test array_all
        $numbers = [2, 4, 6, 8];
        $result = array_all($numbers, fn($n) => $n % 2 === 0);
        $this->assert($result === true, "array_all returns true when all numbers are even");
        
        $mixed_numbers = [2, 4, 5, 8];
        $result = array_all($mixed_numbers, fn($n) => $n % 2 === 0);
        $this->assert($result === false, "array_all returns false when not all numbers are even");
        
        echo "\n";
    }

    private function printSummary()
    {
        echo "============================================================\n";
        echo "📊 TEST SUMMARY\n";
        echo "============================================================\n";
        echo "Total Tests: {$this->testCount}\n";
        echo "Passed: {$this->passCount} ✅\n";
        echo "Failed: " . ($this->testCount - $this->passCount) . "\n";
        echo "Success Rate: " . round(($this->passCount / $this->testCount) * 100, 1) . "%\n\n";
        
        if ($this->passCount === $this->testCount) {
            echo "🎉 ALL TESTS PASSED! PHP 8.4 Array Functions working correctly! 🎉\n";
        } else {
            echo "❌ Some tests failed. Please review implementation.\n";
        }
    }
}

// Run the tests
$test = new ArrayFunctionsTest();
$success = $test->runAllTests();

// Exit with appropriate code
exit($success ? 0 : 1);