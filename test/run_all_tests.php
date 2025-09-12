<?php
/**
 * Flourish Framework - PHP 8.4 Compatibility Test Suite
 * 
 * Execute all tests with: php tests/run_all_tests.php
 * 
 * This script can be run unlimited times to verify compatibility
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

class FlourishTestRunner 
{
    private $results = [];
    private $startTime;
    private $testCount = 0;
    private $passedCount = 0;
    private $failedCount = 0;
    
    public function __construct() 
    {
        $this->startTime = microtime(true);
        echo "🧪 Flourish PHP 8.4 Compatibility Test Suite\n";
        echo "=" . str_repeat("=", 50) . "\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Start Time: " . date('Y-m-d H:i:s') . "\n\n";
    }
    
    public function runAllTests() 
    {
        $testFiles = [
            'php84_compatibility_test.php',
            'modernization_test.php', 
            'deprecated_classes_test.php',
            'working_examples_test.php',
            'fdatabase_compatibility_test.php',
            'fdatabase_real_test.php',
            'fquerybuilder_test.php',
            'mongodb_integration_test.php'
        ];
        
        foreach ($testFiles as $testFile) {
            if (file_exists(__DIR__ . '/' . $testFile)) {
                echo "🔍 Running " . basename($testFile, '.php') . "...\n";
                include __DIR__ . '/' . $testFile;
                echo "\n";
            } else {
                echo "⚠️  Test file missing: $testFile\n\n";
            }
        }
        
        $this->generateReport();
    }
    
    public function test($description, $callable) 
    {
        $this->testCount++;
        echo "  → $description... ";
        
        try {
            $result = $callable();
            if ($result === true) {
                echo "✅ PASS\n";
                $this->passedCount++;
                $this->results[] = ['test' => $description, 'result' => 'PASS', 'message' => ''];
            } else {
                echo "❌ FAIL: $result\n";
                $this->failedCount++;
                $this->results[] = ['test' => $description, 'result' => 'FAIL', 'message' => $result];
            }
        } catch (Exception $e) {
            echo "💥 ERROR: " . $e->getMessage() . "\n";
            $this->failedCount++;
            $this->results[] = ['test' => $description, 'result' => 'ERROR', 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            echo "💥 FATAL: " . $e->getMessage() . "\n";
            $this->failedCount++;
            $this->results[] = ['test' => $description, 'result' => 'FATAL', 'message' => $e->getMessage()];
        }
    }
    
    private function generateReport() 
    {
        $endTime = microtime(true);
        $duration = round($endTime - $this->startTime, 2);
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 TEST SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        echo "Total Tests: $this->testCount\n";
        echo "Passed: $this->passedCount ✅\n";
        echo "Failed: $this->failedCount " . ($this->failedCount > 0 ? "❌" : "✅") . "\n";
        echo "Duration: {$duration}s\n";
        echo "Success Rate: " . round(($this->passedCount / $this->testCount) * 100, 1) . "%\n";
        
        if ($this->failedCount == 0) {
            echo "\n🎉 ALL TESTS PASSED! Flourish is PHP 8.4 compatible! 🎉\n";
        } else {
            echo "\n⚠️  Some tests failed. Review the output above for details.\n";
        }
        
        // Save results to JSON for programmatic access
        $this->saveJsonReport();
    }
    
    private function saveJsonReport() 
    {
        if (!is_dir(__DIR__ . '/lib/reports')) {
            mkdir(__DIR__ . '/lib/reports', 0755, true);
        }
        
        $report = [
            'timestamp' => date('c'),
            'php_version' => PHP_VERSION,
            'total_tests' => $this->testCount,
            'passed' => $this->passedCount,
            'failed' => $this->failedCount,
            'success_rate' => round(($this->passedCount / $this->testCount) * 100, 1),
            'results' => $this->results
        ];
        
        file_put_contents(__DIR__ . '/lib/reports/test_results.json', json_encode($report, JSON_PRETTY_PRINT));
    }
}

// Global test runner instance
$testRunner = new FlourishTestRunner();

// Helper function for tests
function test($description, $callable) {
    global $testRunner;
    return $testRunner->test($description, $callable);
}

// Run all tests
$testRunner->runAllTests();