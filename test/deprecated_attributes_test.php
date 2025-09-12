<?php
/**
 * Test for PHP 8.4 #[\Deprecated] attributes implementation
 * Validates that deprecated methods and classes have proper deprecation attributes
 */

require_once __DIR__ . '/../lib/fDatabase.php';
require_once __DIR__ . '/../lib/fResult.php';
require_once __DIR__ . '/../lib/fEmail.php';

class DeprecatedAttributesTest
{
    private $results = [];
    private $testCount = 0;
    private $passCount = 0;

    public function runAllTests()
    {
        echo "🧪 PHP 8.4 #[\\Deprecated] Attributes Test Suite\n";
        echo "============================================================\n\n";

        $this->testDatabaseDeprecatedMethods();
        $this->testResultDeprecatedClass();
        $this->testEmailDeprecatedMethods();
        $this->testDeprecationReflectionAPI();

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

    private function testDatabaseDeprecatedMethods()
    {
        echo "🗄️ Testing fDatabase Deprecated Methods...\n";
        
        try {
            $reflection = new ReflectionClass('fDatabase');
            
            // Test escape() method has #[\Deprecated] attribute
            if ($reflection->hasMethod('escape')) {
                $escapeMethod = $reflection->getMethod('escape');
                $attributes = $escapeMethod->getAttributes(Deprecated::class);
                
                $this->assert(
                    !empty($attributes),
                    "fDatabase::escape() has #[\\Deprecated] attribute"
                );
                
                if (!empty($attributes)) {
                    $attribute = $attributes[0]->newInstance();
                    $this->assert(
                        str_contains($attribute->message, 'prepared statements'),
                        "Deprecation message mentions prepared statements"
                    );
                    $this->assert(
                        $attribute->since === "2.0",
                        "Deprecation since version is 2.0"
                    );
                }
            }
            
            // Test escapeString() method has #[\Deprecated] attribute (if accessible)
            $methods = $reflection->getMethods(ReflectionMethod::IS_PRIVATE);
            foreach ($methods as $method) {
                if ($method->getName() === 'escapeString') {
                    $attributes = $method->getAttributes(Deprecated::class);
                    $this->assert(
                        !empty($attributes),
                        "fDatabase::escapeString() has #[\\Deprecated] attribute"
                    );
                    break;
                }
            }
            
        } catch (Exception $e) {
            $this->assert(false, "Error testing fDatabase attributes: " . $e->getMessage());
        }
        
        echo "\n";
    }

    private function testResultDeprecatedClass()
    {
        echo "📊 Testing fResult Deprecated Documentation...\n";
        
        try {
            // Since #[\Deprecated] cannot be applied to classes in PHP 8.4,
            // we verify that fResult is marked as deprecated in documentation
            $reflection = new ReflectionClass('fResult');
            $docComment = $reflection->getDocComment();
            
            $this->assert(
                str_contains($docComment, 'DEPRECATED'),
                "fResult class has DEPRECATED warning in documentation"
            );
            
            $this->assert(
                str_contains($docComment, 'mysql_'),
                "Documentation mentions mysql_* functions"
            );
            
            $this->assert(
                str_contains($docComment, 'PHP 8.4'),
                "Documentation mentions PHP 8.4 compatibility"
            );
            
        } catch (Exception $e) {
            $this->assert(false, "Error testing fResult documentation: " . $e->getMessage());
        }
        
        echo "\n";
    }

    private function testEmailDeprecatedMethods()
    {
        echo "📧 Testing fEmail Deprecated Methods...\n";
        
        try {
            $reflection = new ReflectionClass('fEmail');
            
            // Test getSubmissionEmail() method has #[\Deprecated] attribute
            if ($reflection->hasMethod('getSubmissionEmail')) {
                $method = $reflection->getMethod('getSubmissionEmail');
                $attributes = $method->getAttributes(Deprecated::class);
                
                $this->assert(
                    !empty($attributes),
                    "fEmail::getSubmissionEmail() has #[\\Deprecated] attribute"
                );
                
                if (!empty($attributes)) {
                    $attribute = $attributes[0]->newInstance();
                    $this->assert(
                        str_contains($attribute->message, 'property'),
                        "Deprecation message mentions property usage"
                    );
                }
            }
            
        } catch (Exception $e) {
            $this->assert(false, "Error testing fEmail attributes: " . $e->getMessage());
        }
        
        echo "\n";
    }

    private function testDeprecationReflectionAPI()
    {
        echo "🔍 Testing Deprecation Reflection API...\n";
        
        try {
            // Test that PHP 8.4's Deprecated attribute class exists
            $this->assert(
                class_exists('Deprecated'),
                "PHP 8.4 Deprecated attribute class exists"
            );
            
            // Test reflection can find deprecated methods across all classes
            $deprecatedMethods = [];
            
            $classes = ['fDatabase', 'fResult', 'fEmail'];
            foreach ($classes as $className) {
                if (class_exists($className)) {
                    $reflection = new ReflectionClass($className);
                    
                    // Check for deprecated methods (classes can't have #[\Deprecated] in PHP 8.4)
                    $methods = $reflection->getMethods();
                    foreach ($methods as $method) {
                        $attributes = $method->getAttributes(Deprecated::class);
                        if (!empty($attributes)) {
                            $deprecatedMethods[] = $className . '::' . $method->getName();
                        }
                    }
                }
            }
            
            $this->assert(
                count($deprecatedMethods) > 0,
                "Found " . count($deprecatedMethods) . " deprecated methods: " . implode(', ', $deprecatedMethods)
            );
            
            // Test that we have the expected minimum deprecations
            $this->assert(
                count($deprecatedMethods) >= 3,
                "Minimum expected deprecated methods found (3+): " . count($deprecatedMethods) . " found"
            );
            
        } catch (Exception $e) {
            $this->assert(false, "Error testing reflection API: " . $e->getMessage());
        }
        
        echo "\n";
    }

    private function printSummary()
    {
        echo "============================================================\n";
        echo "📊 #[\\DEPRECATED] ATTRIBUTES TEST SUMMARY\n";
        echo "============================================================\n";
        echo "Total Tests: {$this->testCount}\n";
        
        if ($this->passCount === $this->testCount) {
            echo "Passed: {$this->passCount} ✅\n";
            echo "Failed: 0 ✅\n";
            echo "Success Rate: 100%\n\n";
            echo "🎉 ALL DEPRECATION ATTRIBUTES TESTS PASSED! 🎉\n";
            echo "✨ PHP 8.4 #[\\Deprecated] attributes are working correctly!\n";
        } else {
            $failed = $this->testCount - $this->passCount;
            echo "Passed: {$this->passCount}\n";
            echo "Failed: {$failed} ❌\n";
            $rate = round(($this->passCount / $this->testCount) * 100, 1);
            echo "Success Rate: {$rate}%\n\n";
            echo "❌ Some deprecation tests failed. Check implementation.\n";
        }
    }
}

// Run the tests
$tester = new DeprecatedAttributesTest();
$success = $tester->runAllTests();
exit($success ? 0 : 1);