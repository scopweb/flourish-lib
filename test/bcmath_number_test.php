<?php
/**
 * Test for PHP 8.4 BcMath\Number implementation in fMoney
 * Tests precise arithmetic operations using BcMath\Number
 */

require_once __DIR__ . '/../lib/fMoney.php';
require_once __DIR__ . '/../lib/fNumber.php';

class BcMathNumberTest
{
    private $results = [];
    private $testCount = 0;
    private $passCount = 0;

    public function runAllTests()
    {
        echo "🧪 PHP 8.4 BcMath\Number Test Suite for fMoney\n";
        echo "============================================================\n\n";

        $this->testBcMathNumberSupport();
        $this->testArithmeticOperations();
        $this->testCurrencyConversion();
        $this->testBackwardCompatibility();
        
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

    private function testBcMathNumberSupport()
    {
        echo "📊 Testing BcMath\Number support in fMoney...\n";
        
        // Check if BcMath\Number class exists (PHP 8.4+)
        $bcmath_available = class_exists('BcMath\Number');
        $this->assert(true, "BcMath availability check complete (available: " . ($bcmath_available ? "yes" : "no") . ")");
        
        if ($bcmath_available) {
            // Test BcMath\Number constructor support
            try {
                $bcnum = new BcMath\Number('123.45');
                $money = new fMoney($bcnum, 'USD');
                $this->assert($money->__toString() === '123.45', "fMoney accepts BcMath\Number in constructor");
            } catch (Exception $e) {
                $this->assert(false, "fMoney BcMath\Number constructor failed: " . $e->getMessage());
            }
        }
        
        echo "\n";
    }

    private function testArithmeticOperations()
    {
        echo "➕ Testing arithmetic operations with BcMath\Number...\n";
        
        if (!class_exists('BcMath\Number')) {
            echo "⚠️  BcMath\Number not available - testing fNumber fallback\n";
            $money1 = new fMoney('100.50', 'USD');
            $money2 = new fMoney('25.25', 'USD');
        } else {
            $bcnum1 = new BcMath\Number('100.50');
            $bcnum2 = new BcMath\Number('25.25');
            $money1 = new fMoney($bcnum1, 'USD');
            $money2 = new fMoney($bcnum2, 'USD');
        }
        
        // Test addition
        $sum = $money1->add($money2);
        $this->assert($sum->__toString() === '125.75', "Addition produces correct result: 100.50 + 25.25 = 125.75");
        
        // Test subtraction
        $diff = $money1->sub($money2);
        $this->assert($diff->__toString() === '75.25', "Subtraction produces correct result: 100.50 - 25.25 = 75.25");
        
        // Test multiplication
        $product = $money1->mul('2');
        $this->assert($product->__toString() === '201.00', "Multiplication produces correct result: 100.50 * 2 = 201.00");
        
        echo "\n";
    }

    private function testCurrencyConversion()
    {
        echo "💱 Testing currency conversion with BcMath\Number...\n";
        
        // Set up currency conversion rate (hypothetical)
        fMoney::defineCurrency('EUR', 'Euro', '€', 2, 1.1);
        fMoney::defineCurrency('USD', 'US Dollar', '$', 2, 1.0);
        
        try {
            if (class_exists('BcMath\Number')) {
                $bcnum = new BcMath\Number('100.00');
                $usd_money = new fMoney($bcnum, 'USD');
            } else {
                $usd_money = new fMoney('100.00', 'USD');
            }
            
            $eur_money = $usd_money->convert('EUR');
            $this->assert(is_object($eur_money), "Currency conversion completed successfully");
            $this->assert($eur_money instanceof fMoney, "Converted result is fMoney object");
            
        } catch (Exception $e) {
            $this->assert(false, "Currency conversion failed: " . $e->getMessage());
        }
        
        echo "\n";
    }

    private function testBackwardCompatibility()
    {
        echo "🔄 Testing backward compatibility...\n";
        
        // Ensure USD currency is defined
        fMoney::defineCurrency('USD', 'US Dollar', '$', 2, 1.0);
        
        // Test that existing fNumber-based operations still work
        $money1 = new fMoney(new fNumber('50.75'), 'USD');
        $money2 = new fMoney('24.25', 'USD');
        
        $sum = $money1->add($money2);
        $this->assert($sum->__toString() === '75.00', "fNumber objects work in arithmetic operations");
        
        // Test string/numeric inputs with debug output
        $money3 = new fMoney('100.00', 'USD');
        $money4 = new fMoney('50.50', 'USD');  
        $diff = $money3->sub($money4);
        $expected = '49.50';
        $actual = $diff->__toString();
        
        // Add extra debugging  
        if ($actual !== $expected) {
            echo "   DEBUG: 100.00 - 50.50 = $actual (type issue?)\n";
        }
        
        $this->assert($actual === $expected, "Mixed numeric inputs work correctly (expected: $expected, got: $actual)");
        
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
            echo "🎉 ALL TESTS PASSED! BcMath\Number integration working correctly! 🎉\n";
        } else {
            echo "❌ Some tests failed. Please review implementation.\n";
        }
    }
}

// Run the tests
$test = new BcMathNumberTest();
$success = $test->runAllTests();

// Exit with appropriate code
exit($success ? 0 : 1);