<?php
/**
 * Email encoding enumeration for fEmail class
 * 
 * Provides type safety for email encoding methods supported by Flourish Framework.
 * Each encoding includes usage guidelines and validation methods.
 * 
 * @since PHP 8.4
 */
enum EmailEncoding: string 
{
    /**
     * UTF-8 character encoding - recommended for modern emails
     */
    case UTF8 = 'utf-8';
    
    /**
     * Base64 encoding - for binary content and attachments
     */
    case BASE64 = 'base64';
    
    /**
     * Quoted-Printable encoding - for text with some special characters
     */
    case QUOTED_PRINTABLE = 'quoted-printable';
    
    /**
     * 7bit encoding - for plain ASCII text only
     */
    case SEVEN_BIT = '7bit';
    
    /**
     * 8bit encoding - for extended ASCII
     */
    case EIGHT_BIT = '8bit';
    
    /**
     * Binary encoding - for raw binary data
     */
    case BINARY = 'binary';

    /**
     * Check if this encoding is suitable for text content
     * 
     * @return bool True if suitable for text
     */
    public function isTextSuitable(): bool
    {
        return match($this) {
            self::UTF8, self::QUOTED_PRINTABLE, self::SEVEN_BIT => true,
            self::BASE64, self::EIGHT_BIT, self::BINARY => false
        };
    }

    /**
     * Check if this encoding is suitable for binary content
     * 
     * @return bool True if suitable for binary data
     */
    public function isBinarySuitable(): bool
    {
        return match($this) {
            self::BASE64, self::BINARY => true,
            self::UTF8, self::QUOTED_PRINTABLE, self::SEVEN_BIT, self::EIGHT_BIT => false
        };
    }

    /**
     * Check if this encoding requires special handling
     * 
     * @return bool True if requires encoding/decoding
     */
    public function requiresSpecialHandling(): bool
    {
        return match($this) {
            self::BASE64, self::QUOTED_PRINTABLE => true,
            self::UTF8, self::SEVEN_BIT, self::EIGHT_BIT, self::BINARY => false
        };
    }

    /**
     * Get the maximum line length for this encoding
     * 
     * @return int Maximum characters per line
     */
    public function getMaxLineLength(): int
    {
        return match($this) {
            self::BASE64 => 76,
            self::QUOTED_PRINTABLE => 75,
            self::UTF8, self::SEVEN_BIT, self::EIGHT_BIT, self::BINARY => 998 // RFC 5322 limit
        };
    }

    /**
     * Check if this encoding supports international characters
     * 
     * @return bool True if supports international characters
     */
    public function supportsInternational(): bool
    {
        return match($this) {
            self::UTF8, self::BASE64, self::QUOTED_PRINTABLE => true,
            self::SEVEN_BIT, self::EIGHT_BIT, self::BINARY => false
        };
    }

    /**
     * Get recommended use case for this encoding
     * 
     * @return string Description of recommended usage
     */
    public function getRecommendedUsage(): string
    {
        return match($this) {
            self::UTF8 => 'Modern text content with international characters',
            self::BASE64 => 'Binary attachments and files',
            self::QUOTED_PRINTABLE => 'Text with occasional special characters',
            self::SEVEN_BIT => 'Plain ASCII text only',
            self::EIGHT_BIT => 'Extended ASCII text (legacy)',
            self::BINARY => 'Raw binary data (not recommended for email)'
        };
    }

    /**
     * Get the MIME header format for this encoding
     * 
     * @return string MIME header value
     */
    public function getMimeHeader(): string
    {
        return "Content-Transfer-Encoding: {$this->value}";
    }

    /**
     * Check if this encoding is email-safe (recommended for email use)
     * 
     * @return bool True if safe for email transmission
     */
    public function isEmailSafe(): bool
    {
        return match($this) {
            self::UTF8, self::BASE64, self::QUOTED_PRINTABLE, self::SEVEN_BIT => true,
            self::EIGHT_BIT, self::BINARY => false
        };
    }

    /**
     * Get efficiency rating for this encoding (1-5, 5 being most efficient)
     * 
     * @return int Efficiency rating
     */
    public function getEfficiencyRating(): int
    {
        return match($this) {
            self::SEVEN_BIT => 5,
            self::UTF8 => 4,
            self::EIGHT_BIT => 4,
            self::QUOTED_PRINTABLE => 3,
            self::BASE64 => 2,
            self::BINARY => 1
        };
    }

    /**
     * Get the character set commonly used with this encoding
     * 
     * @return string Character set name
     */
    public function getCommonCharset(): string
    {
        return match($this) {
            self::UTF8 => 'utf-8',
            self::SEVEN_BIT => 'us-ascii',
            self::EIGHT_BIT => 'iso-8859-1',
            self::BASE64, self::QUOTED_PRINTABLE => 'utf-8',
            self::BINARY => 'binary'
        };
    }

    /**
     * Create EmailEncoding from MIME header value
     * 
     * @param string $header Content-Transfer-Encoding header value
     * @return EmailEncoding|null EmailEncoding instance or null if not found
     */
    public static function fromMimeHeader(string $header): ?self
    {
        // Extract encoding from header like "Content-Transfer-Encoding: base64"
        $encoding = trim(str_ireplace('content-transfer-encoding:', '', $header));
        return self::tryFrom(strtolower($encoding));
    }

    /**
     * Get the best encoding for given content
     * 
     * @param string $content Content to analyze
     * @param bool $isText Whether content is text (vs binary)
     * @return EmailEncoding Recommended encoding
     */
    public static function getBestForContent(string $content, bool $isText = true): self
    {
        if (!$isText) {
            return self::BASE64;
        }

        // Check if content is pure ASCII
        if (mb_check_encoding($content, 'ASCII')) {
            return self::SEVEN_BIT;
        }

        // Check if content has international characters
        if (mb_check_encoding($content, 'UTF-8') && !mb_check_encoding($content, 'ASCII')) {
            // If mostly ASCII with few special chars, use quoted-printable
            $specialChars = preg_match_all('/[^\x20-\x7E]/', $content);
            $totalChars = strlen($content);
            
            if ($specialChars / $totalChars < 0.1) {
                return self::QUOTED_PRINTABLE;
            }
            
            return self::UTF8;
        }

        return self::EIGHT_BIT;
    }

    /**
     * Get all text-suitable encodings
     * 
     * @return array Array of text-suitable EmailEncoding cases
     */
    public static function getTextEncodings(): array
    {
        return array_filter(self::cases(), fn($encoding) => $encoding->isTextSuitable());
    }

    /**
     * Get all binary-suitable encodings
     * 
     * @return array Array of binary-suitable EmailEncoding cases
     */
    public static function getBinaryEncodings(): array
    {
        return array_filter(self::cases(), fn($encoding) => $encoding->isBinarySuitable());
    }
}