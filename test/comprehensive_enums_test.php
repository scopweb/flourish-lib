<?php
/**
 * Comprehensive test for all implemented PHP 8.4 Enums
 * Tests JsonToken, DatabaseType, MimeType, ImageFormat, and EmailEncoding
 */

require_once __DIR__ . '/../lib/fJSON.php';
require_once __DIR__ . '/../DatabaseType.php';
require_once __DIR__ . '/../MimeType.php';
require_once __DIR__ . '/../ImageFormat.php';
require_once __DIR__ . '/../EmailEncoding.php';

class ComprehensiveEnumsTest
{
    private $results = [];
    private $testCount = 0;
    private $passCount = 0;

    public function runAllTests()
    {
        echo "🧪 Comprehensive Enums Test Suite\n";
        echo "============================================================\n\n";

        $this->testJsonTokenEnum();
        $this->testDatabaseTypeEnum();
        $this->testMimeTypeEnum();
        $this->testImageFormatEnum();
        $this->testEmailEncodingEnum();

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

    private function testJsonTokenEnum()
    {
        echo "📋 Testing JsonToken Enum...\n";
        
        // Test basic enum functionality
        $this->assert(JsonToken::ObjectOpen instanceof JsonToken, "JsonToken::ObjectOpen is instance of JsonToken");
        $this->assert(JsonToken::ObjectOpen->value === 3, "JsonToken::ObjectOpen has correct integer value");
        
        // Test category methods
        $this->assert(JsonToken::ObjectOpen->isStructural(), "ObjectOpen is structural");
        $this->assert(JsonToken::String->isValue(), "String is value token");
        $this->assert(JsonToken::ArrayOpen->isOpeningToken(), "ArrayOpen is opening token");
        $this->assert(JsonToken::ArrayClose->isClosingToken(), "ArrayClose is closing token");
        
        // Test token matching
        $closing = JsonToken::ObjectOpen->getMatchingClosingToken();
        $this->assert($closing === JsonToken::ObjectClose, "ObjectOpen matches with ObjectClose");
        
        // Test display names
        $this->assert(JsonToken::ObjectOpen->getDisplayName() === '{', "ObjectOpen displays as '{'");
        $this->assert(JsonToken::String->getDisplayName() === 'string', "String displays as 'string'");
        
        // Test legacy conversion
        $legacy = JsonToken::Integer->toConstant();
        $this->assert($legacy === 8, "Integer converts to correct constant");
        
        $fromConstant = JsonToken::fromConstant(8);
        $this->assert($fromConstant === JsonToken::Integer, "Can create from constant");
        
        // Test all cases exist
        $cases = JsonToken::cases();
        $this->assert(count($cases) === 14, "JsonToken has 14 cases");
        
        echo "\n";
    }

    private function testDatabaseTypeEnum()
    {
        echo "🗄️ Testing DatabaseType Enum...\n";
        
        // Test basic enum functionality
        $this->assert(DatabaseType::MySQL instanceof DatabaseType, "DatabaseType::MySQL is instance of DatabaseType");
        $this->assert(DatabaseType::MySQL->value === 'mysql', "DatabaseType::MySQL has correct value");
        
        // Test extension detection
        $extensions = DatabaseType::MySQL->getPreferredExtensions();
        $this->assert(is_array($extensions) && count($extensions) > 0, "MySQL has preferred extensions");
        $this->assert(in_array('mysqli', $extensions), "MySQL includes mysqli extension");
        
        // Test display names
        $this->assert(DatabaseType::MySQL->getDisplayName() === 'MySQL', "MySQL has correct display name");
        $this->assert(DatabaseType::PostgreSQL->getDisplayName() === 'PostgreSQL', "PostgreSQL has correct display name");
        
        // Test modern database classification
        $this->assert(DatabaseType::MySQL->isModern(), "MySQL is classified as modern");
        $this->assert(DatabaseType::SQLite->isModern(), "SQLite is classified as modern");
        
        // Test factory method
        $db = DatabaseType::fromString('mysql');
        $this->assert($db === DatabaseType::MySQL, "Can create DatabaseType from string");
        
        // Test all cases exist
        $cases = DatabaseType::cases();
        $this->assert(count($cases) === 6, "DatabaseType has 6 cases");
        
        echo "\n";
    }

    private function testMimeTypeEnum()
    {
        echo "📄 Testing MimeType Enum...\n";
        
        // Test basic enum functionality
        $this->assert(MimeType::ImageJpeg instanceof MimeType, "MimeType::ImageJpeg is instance of MimeType");
        $this->assert(MimeType::ImageJpeg->value === 'image/jpeg', "ImageJpeg has correct value");
        
        // Test category detection
        $this->assert(MimeType::ImageJpeg->getCategory() === 'image', "JPEG is image category");
        $this->assert(MimeType::VideoMp4->getCategory() === 'video', "MP4 is video category");
        $this->assert(MimeType::AudioMpeg->getCategory() === 'audio', "MPEG is audio category");
        
        // Test type checking methods
        $this->assert(MimeType::ImageJpeg->isImage(), "JPEG is image type");
        $this->assert(MimeType::VideoMp4->isVideo(), "MP4 is video type");
        $this->assert(MimeType::AudioMpeg->isAudio(), "MPEG is audio type");
        
        // Test web safety
        $this->assert(MimeType::ImageJpeg->isWebSafe(), "JPEG is web-safe");
        $this->assert(MimeType::ApplicationPdf->isWebSafe(), "PDF is web-safe");
        
        // Test extensions
        $this->assert(MimeType::ImageJpeg->getTypicalExtension() === 'jpg', "JPEG has correct extension");
        $this->assert(MimeType::ApplicationPdf->getTypicalExtension() === 'pdf', "PDF has correct extension");
        
        // Test category filtering
        $imageTypes = MimeType::getByCategory('image');
        $this->assert(count($imageTypes) > 0, "Has image types");
        $this->assert(in_array(MimeType::ImageJpeg, $imageTypes), "JPEG is in image types");
        
        echo "\n";
    }

    private function testImageFormatEnum()
    {
        echo "🖼️ Testing ImageFormat Enum...\n";
        
        // Test basic enum functionality
        $this->assert(ImageFormat::JPEG instanceof ImageFormat, "ImageFormat::JPEG is instance of ImageFormat");
        $this->assert(ImageFormat::JPEG->value === 'jpg', "JPEG has correct value");
        
        // Test MIME type mapping
        $this->assert(ImageFormat::JPEG->getMimeType() === 'image/jpeg', "JPEG has correct MIME type");
        $this->assert(ImageFormat::PNG->getMimeType() === 'image/png', "PNG has correct MIME type");
        
        // Test transparency support
        $this->assert(ImageFormat::PNG->supportsTransparency(), "PNG supports transparency");
        $this->assert(!ImageFormat::JPEG->supportsTransparency(), "JPEG doesn't support transparency");
        
        // Test animation support
        $this->assert(ImageFormat::GIF->supportsAnimation(), "GIF supports animation");
        $this->assert(!ImageFormat::PNG->supportsAnimation(), "PNG doesn't support animation");
        
        // Test compression type
        $this->assert(ImageFormat::JPEG->isLossy(), "JPEG is lossy");
        $this->assert(!ImageFormat::PNG->isLossy(), "PNG is not lossy");
        
        // Test web safety
        $this->assert(ImageFormat::JPEG->isWebSafe(), "JPEG is web-safe");
        $this->assert(ImageFormat::PNG->isWebSafe(), "PNG is web-safe");
        
        // Test extensions
        $extensions = ImageFormat::JPEG->getExtensions();
        $this->assert(in_array('jpg', $extensions), "JPEG includes jpg extension");
        $this->assert(in_array('jpeg', $extensions), "JPEG includes jpeg extension");
        
        // Test factory methods
        $format = ImageFormat::fromExtension('jpg');
        $this->assert($format === ImageFormat::JPEG, "Can create from extension");
        
        $format = ImageFormat::fromMimeType('image/png');
        $this->assert($format === ImageFormat::PNG, "Can create from MIME type");
        
        // Test web-safe formats
        $webSafe = ImageFormat::getWebSafeFormats();
        $this->assert(count($webSafe) > 0, "Has web-safe formats");
        
        echo "\n";
    }

    private function testEmailEncodingEnum()
    {
        echo "📧 Testing EmailEncoding Enum...\n";
        
        // Test basic enum functionality
        $this->assert(EmailEncoding::UTF8 instanceof EmailEncoding, "EmailEncoding::UTF8 is instance of EmailEncoding");
        $this->assert(EmailEncoding::UTF8->value === 'utf-8', "UTF8 has correct value");
        
        // Test text suitability
        $this->assert(EmailEncoding::UTF8->isTextSuitable(), "UTF8 is text suitable");
        $this->assert(EmailEncoding::SEVEN_BIT->isTextSuitable(), "7BIT is text suitable");
        $this->assert(!EmailEncoding::BASE64->isTextSuitable(), "BASE64 is not text suitable");
        
        // Test binary suitability
        $this->assert(EmailEncoding::BASE64->isBinarySuitable(), "BASE64 is binary suitable");
        $this->assert(EmailEncoding::BINARY->isBinarySuitable(), "BINARY is binary suitable");
        $this->assert(!EmailEncoding::UTF8->isBinarySuitable(), "UTF8 is not binary suitable");
        
        // Test special handling requirements
        $this->assert(EmailEncoding::BASE64->requiresSpecialHandling(), "BASE64 requires special handling");
        $this->assert(EmailEncoding::QUOTED_PRINTABLE->requiresSpecialHandling(), "QUOTED_PRINTABLE requires special handling");
        $this->assert(!EmailEncoding::UTF8->requiresSpecialHandling(), "UTF8 doesn't require special handling");
        
        // Test line length limits
        $this->assert(EmailEncoding::BASE64->getMaxLineLength() === 76, "BASE64 has 76 char limit");
        $this->assert(EmailEncoding::QUOTED_PRINTABLE->getMaxLineLength() === 75, "QP has 75 char limit");
        
        // Test international support
        $this->assert(EmailEncoding::UTF8->supportsInternational(), "UTF8 supports international");
        $this->assert(!EmailEncoding::SEVEN_BIT->supportsInternational(), "7BIT doesn't support international");
        
        // Test email safety
        $this->assert(EmailEncoding::UTF8->isEmailSafe(), "UTF8 is email safe");
        $this->assert(EmailEncoding::BASE64->isEmailSafe(), "BASE64 is email safe");
        $this->assert(!EmailEncoding::BINARY->isEmailSafe(), "BINARY is not email safe");
        
        // Test MIME header generation
        $header = EmailEncoding::BASE64->getMimeHeader();
        $this->assert($header === 'Content-Transfer-Encoding: base64', "BASE64 generates correct header");
        
        // Test factory methods
        $encoding = EmailEncoding::fromMimeHeader('Content-Transfer-Encoding: quoted-printable');
        $this->assert($encoding === EmailEncoding::QUOTED_PRINTABLE, "Can create from MIME header");
        
        // Test content analysis
        $encoding = EmailEncoding::getBestForContent('Hello World'); // ASCII content
        $this->assert($encoding === EmailEncoding::SEVEN_BIT, "Recommends 7BIT for ASCII");
        
        $encoding = EmailEncoding::getBestForContent('Binary content', false); // Binary flag
        $this->assert($encoding === EmailEncoding::BASE64, "Recommends BASE64 for binary");
        
        // Test category filters
        $textEncodings = EmailEncoding::getTextEncodings();
        $this->assert(count($textEncodings) > 0, "Has text encodings");
        $this->assert(in_array(EmailEncoding::UTF8, $textEncodings), "UTF8 is in text encodings");
        
        $binaryEncodings = EmailEncoding::getBinaryEncodings();
        $this->assert(count($binaryEncodings) > 0, "Has binary encodings");
        $this->assert(in_array(EmailEncoding::BASE64, $binaryEncodings), "BASE64 is in binary encodings");
        
        echo "\n";
    }

    private function printSummary()
    {
        echo "============================================================\n";
        echo "📊 COMPREHENSIVE ENUMS TEST SUMMARY\n";
        echo "============================================================\n";
        echo "Total Tests: {$this->testCount}\n";
        
        if ($this->passCount === $this->testCount) {
            echo "Passed: {$this->passCount} ✅\n";
            echo "Failed: 0 ✅\n";
            echo "Success Rate: 100%\n\n";
            echo "🎉 ALL ENUM TESTS PASSED! All 5 enums are working correctly! 🎉\n";
        } else {
            $failed = $this->testCount - $this->passCount;
            echo "Passed: {$this->passCount}\n";
            echo "Failed: {$failed} ❌\n";
            $rate = round(($this->passCount / $this->testCount) * 100, 1);
            echo "Success Rate: {$rate}%\n\n";
            echo "❌ Some tests failed. Please check the implementation.\n";
        }
    }
}

// Run the tests
$tester = new ComprehensiveEnumsTest();
$success = $tester->runAllTests();
exit($success ? 0 : 1);