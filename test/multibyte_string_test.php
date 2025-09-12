<?php
/**
 * Test for PHP 8.4 Multibyte String Support improvements
 * Tests str_contains, str_starts_with, str_ends_with functions
 */

require_once __DIR__ . '/../lib/fGrammar.php';
require_once __DIR__ . '/../lib/fFilesystem.php';
require_once __DIR__ . '/../lib/fFile.php';
require_once __DIR__ . '/../lib/fException.php';
require_once __DIR__ . '/../lib/fText.php';
require_once __DIR__ . '/../lib/fUTF8.php';
require_once __DIR__ . '/../lib/fDirectory.php';
require_once __DIR__ . '/../lib/fImage.php';

class MultibyteStringTest
{
    private $results = [];
    private $testCount = 0;
    private $passCount = 0;

    public function runAllTests()
    {
        echo "🧪 PHP 8.4 Multibyte String Test Suite\n";
        echo "============================================================\n\n";

        $this->testNewStringFunctions();
        $this->testGrammarStringHandling();
        $this->testFileStringHandling();
        $this->testUTF8Improvements();
        $this->testDirectoryStringHandling();
        
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

    private function testNewStringFunctions()
    {
        echo "🔤 Testing PHP 8.4 string functions availability...\n";
        
        $this->assert(function_exists('str_contains'), "str_contains() function available");
        $this->assert(function_exists('str_starts_with'), "str_starts_with() function available");
        $this->assert(function_exists('str_ends_with'), "str_ends_with() function available");
        
        // Test basic functionality
        $this->assert(str_contains('hello world', 'world'), "str_contains() works correctly");
        $this->assert(str_starts_with('hello world', 'hello'), "str_starts_with() works correctly");
        $this->assert(str_ends_with('hello world', 'world'), "str_ends_with() works correctly");
        
        echo "\n";
    }

    private function testGrammarStringHandling()
    {
        echo "📝 Testing fGrammar string handling improvements...\n";
        
        // Test camelize method with spaces
        $result = fGrammar::camelize('hello world test', true);
        $this->assert($result === 'HelloWorldTest', "fGrammar::camelize handles spaces correctly");
        
        // Test underscorize conversion
        $result = fGrammar::underscorize('HelloWorld');
        $this->assert($result === 'hello_world', "fGrammar::underscorize works correctly");
        
        // Test humanize method
        $result = fGrammar::humanize('test_string_value');
        $this->assert($result === 'Test String Value', "fGrammar::humanize works correctly");
        
        echo "\n";
    }

    private function testFileStringHandling()
    {
        echo "📁 Testing fFile string handling improvements...\n";
        
        // Create a temporary PHP file to test detection
        $php_content = '<?php echo "Hello World"; ?>';
        $temp_file = tempnam(sys_get_temp_dir(), 'test_php');
        file_put_contents($temp_file, $php_content);
        
        try {
            $file = new fFile($temp_file);
            $mime_type = $file->getMimeType();
            $this->assert(str_contains($mime_type, 'php') || $mime_type === 'application/x-httpd-php', "PHP file detection works with str_contains()");
        } catch (Exception $e) {
            $this->assert(false, "PHP file detection failed: " . $e->getMessage());
        } finally {
            unlink($temp_file);
        }
        
        echo "\n";
    }

    private function testUTF8Improvements()
    {
        echo "🌍 Testing fUTF8 improvements...\n";
        
        // Test Unicode code point handling
        $char = fUTF8::chr('U+1F600'); // 😀 emoji
        $this->assert(strlen($char) > 1, "Unicode emoji character created correctly");
        
        // Test UTF-8 string length
        $utf8_string = 'Hello 世界';
        $length = fUTF8::len($utf8_string);
        $this->assert($length === 8, "UTF-8 string length calculated correctly: expected 8, got $length");
        
        // Test substring (using correct method name)
        $substring = fUTF8::sub($utf8_string, 6, 2);
        $this->assert($substring === '世界', "UTF-8 substring extraction works correctly");
        
        echo "\n";
    }

    private function testDirectoryStringHandling()
    {
        echo "📂 Testing fDirectory string handling improvements...\n";
        
        // Test directory path handling (without actually creating directories)
        // We can test the string logic used in path validation
        
        $test_path1 = '/home/user/documents';
        $test_path2 = '/home/user';
        
        $starts_with_result = str_starts_with($test_path1, $test_path2);
        $this->assert($starts_with_result === true, "str_starts_with() correctly identifies path hierarchy");
        
        $test_path3 = '/home/other';
        $starts_with_result2 = str_starts_with($test_path1, $test_path3);
        $this->assert($starts_with_result2 === false, "str_starts_with() correctly rejects non-matching paths");
        
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
            echo "🎉 ALL TESTS PASSED! Multibyte string improvements working correctly! 🎉\n";
        } else {
            echo "❌ Some tests failed. Please review implementation.\n";
        }
    }
}

// Run the tests
$test = new MultibyteStringTest();
$success = $test->runAllTests();

// Exit with appropriate code
exit($success ? 0 : 1);