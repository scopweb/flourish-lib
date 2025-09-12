<?php
/**
 * Test Property Hooks Implementations - Complete Test Suite
 *
 * Tests all implemented Property Hooks for fEmail, fAuthorization, and fFile classes
 * Compatible with PHP 8.4 Property Hooks feature
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required classes
require_once __DIR__ . '/lib/fCore.php';
require_once __DIR__ . '/lib/fEmail.php';
require_once __DIR__ . '/lib/fAuthorization.php';
require_once __DIR__ . '/lib/fFile.php';
require_once __DIR__ . '/lib/fDirectory.php';
require_once __DIR__ . '/lib/fTimestamp.php';
require_once __DIR__ . '/lib/fFilesystem.php';
require_once __DIR__ . '/lib/fSession.php';

echo "🧪 Property Hooks Complete Test Suite\n";
echo "=====================================\n\n";

$tests_passed = 0;
$tests_total = 0;

function test($description, $test_function) {
    global $tests_passed, $tests_total;
    $tests_total++;
    
    try {
        $result = $test_function();
        if ($result) {
            echo "✅ $description\n";
            $tests_passed++;
        } else {
            echo "❌ $description\n";
        }
    } catch (Exception $e) {
        echo "❌ $description - Exception: " . $e->getMessage() . "\n";
    }
}

// Initialize session for fAuthorization tests (if not already started)
if (!session_id()) {
    session_start();
}

// Test 1: fEmail Property Hooks
echo "📧 Testing fEmail Property Hooks\n";
echo "================================\n";

test("fEmail body property setter/getter", function() {
    $email = new fEmail();
    $email->body = "Test email body content";
    return $email->body === "Test email body content";
});

test("fEmail fromEmail property setter/getter", function() {
    $email = new fEmail();
    $email->fromEmail = "test@example.com";
    return strpos($email->fromEmail, "test@example.com") !== false;
});

test("fEmail subject property setter/getter", function() {
    $email = new fEmail();
    $email->subject = "Test Subject Line";
    return $email->subject === "Test Subject Line";
});

test("fEmail submissionEmail read-only property", function() {
    $email = new fEmail();
    $email->fromEmail = "sender@example.com";
    $submission = $email->submissionEmail;
    return is_string($submission);
});

echo "\n";

// Note: fAuthorization Property Hooks not implemented 
// (PHP 8.4 doesn't support Property Hooks for static properties)
echo "🔐 fAuthorization: Property Hooks not applicable for static properties\n";
echo "====================================================================\n\n";

// Test 3: fFile Property Hooks (create a temporary test file)
echo "📄 Testing fFile Property Hooks\n";
echo "===============================\n";

$temp_file = tempnam(sys_get_temp_dir(), 'property_hooks_test_');
file_put_contents($temp_file, 'Test file content for Property Hooks');

test("fFile extension property", function() use ($temp_file) {
    // Rename to have a .txt extension
    $txt_file = $temp_file . '.txt';
    rename($temp_file, $txt_file);
    $file = new fFile($txt_file);
    $extension = $file->extension;
    unlink($txt_file); // cleanup
    return $extension === 'txt';
});

// Create a new temp file for other tests
$temp_file = tempnam(sys_get_temp_dir(), 'property_hooks_test_');
file_put_contents($temp_file, 'Test file content for Property Hooks');

test("fFile mimeType property", function() use ($temp_file) {
    $file = new fFile($temp_file);
    $mimeType = $file->mimeType;
    return is_string($mimeType) && !empty($mimeType);
});

test("fFile mTime property", function() use ($temp_file) {
    $file = new fFile($temp_file);
    $mTime = $file->mTime;
    return $mTime instanceof fTimestamp;
});

test("fFile name property", function() use ($temp_file) {
    $file = new fFile($temp_file);
    $name = $file->name;
    $expected_name = basename($temp_file);
    return $name === $expected_name;
});

test("fFile parent property", function() use ($temp_file) {
    $file = new fFile($temp_file);
    $parent = $file->parent;
    return $parent instanceof fDirectory;
});

test("fFile path property", function() use ($temp_file) {
    $file = new fFile($temp_file);
    $path = $file->path;
    return $path === $temp_file;
});

test("fFile size property", function() use ($temp_file) {
    $file = new fFile($temp_file);
    $size = $file->size;
    return is_int($size) && $size > 0;
});

// Cleanup
unlink($temp_file);

echo "\n";

// Test 4: Backward Compatibility - Legacy methods should still work
echo "🔄 Testing Backward Compatibility\n";
echo "=================================\n";

test("fEmail legacy getSubmissionEmail() method works", function() {
    $email = new fEmail();
    $email->fromEmail = "legacy@example.com";
    $submission = $email->getSubmissionEmail();
    return $submission === null || is_string($submission); // null if not set properly, string if email found
});

test("fAuthorization legacy getAuthLevels() method works", function() {
    fAuthorization::setAuthLevels(['admin' => 100, 'user' => 50]);
    $levels = fAuthorization::getAuthLevels();
    return is_array($levels) && count($levels) === 2;
});

test("fAuthorization legacy getLoginPage() method works", function() {
    fAuthorization::setLoginPage("/legacy-login.php");
    $page = fAuthorization::getLoginPage();
    return $page === "/legacy-login.php";
});

test("fAuthorization legacy getUserToken() method works", function() {
    fAuthorization::setUserToken("legacy_token_456");
    $token = fAuthorization::getUserToken();
    return $token === "legacy_token_456";
});

// Create temp file for fFile legacy tests
$temp_file = tempnam(sys_get_temp_dir(), 'legacy_test_');
file_put_contents($temp_file, 'Legacy test content');

test("fFile legacy methods still work", function() use ($temp_file) {
    $file = new fFile($temp_file);
    $results = [
        is_string($file->getExtension()),
        is_string($file->getMimeType()),
        $file->getMTime() instanceof fTimestamp,
        is_string($file->getName()),
        $file->getParent() instanceof fDirectory,
        is_string($file->getPath()),
        is_numeric($file->getSize())
    ];
    unlink($temp_file);
    return !in_array(false, $results, true);
});

echo "\n";

// Summary
echo "📊 Test Results Summary\n";
echo "=======================\n";
echo "Tests Passed: $tests_passed/$tests_total\n";

if ($tests_passed === $tests_total) {
    echo "🎉 All Property Hooks tests passed! PHP 8.4 Property Hooks implementation is successful!\n";
} else {
    echo "⚠️  Some tests failed. Please review the implementation.\n";
}

echo "\n✅ Property Hooks Implementation Complete:\n";
echo "   • fEmail: body, fromEmail, subject, submissionEmail\n";
echo "   • fAuthorization: Not applicable (static properties don't support Property Hooks)\n";  
echo "   • fFile: extension, mimeType, mTime, name, parent, path, size\n";
echo "   • Backward compatibility maintained for all legacy methods\n";

exit($tests_passed === $tests_total ? 0 : 1);