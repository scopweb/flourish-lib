<?php
/**
 * Image format enumeration for fImage class
 * 
 * Provides type safety for image formats supported by Flourish Framework.
 * Each format includes validation, quality settings, and processing capabilities.
 * 
 * @since PHP 8.4
 */
enum ImageFormat: string 
{
    /**
     * JPEG format - lossy compression, good for photos
     */
    case JPEG = 'jpg';
    
    /**
     * PNG format - lossless compression, supports transparency
     */
    case PNG = 'png';
    
    /**
     * GIF format - lossless compression, supports animation
     */
    case GIF = 'gif';
    
    /**
     * TIFF format - lossless compression, professional images
     */
    case TIFF = 'tif';
    
    /**
     * BMP format - uncompressed bitmap
     */
    case BMP = 'bmp';
    
    /**
     * WebP format - modern web format with better compression
     */
    case WEBP = 'webp';
    
    /**
     * ICO format - Windows icon format
     */
    case ICO = 'ico';

    /**
     * Get the MIME type for this image format
     * 
     * @return string MIME type
     */
    public function getMimeType(): string
    {
        return match($this) {
            self::JPEG => 'image/jpeg',
            self::PNG => 'image/png',
            self::GIF => 'image/gif',
            self::TIFF => 'image/tiff',
            self::BMP => 'image/x-ms-bmp',
            self::WEBP => 'image/webp',
            self::ICO => 'application/vnd.microsoft.icon'
        };
    }

    /**
     * Check if this format supports transparency
     * 
     * @return bool True if format supports transparency
     */
    public function supportsTransparency(): bool
    {
        return match($this) {
            self::PNG, self::GIF, self::WEBP, self::ICO => true,
            self::JPEG, self::TIFF, self::BMP => false
        };
    }

    /**
     * Check if this format supports animation
     * 
     * @return bool True if format supports animation
     */
    public function supportsAnimation(): bool
    {
        return match($this) {
            self::GIF, self::WEBP => true,
            self::JPEG, self::PNG, self::TIFF, self::BMP, self::ICO => false
        };
    }

    /**
     * Check if this format uses lossy compression
     * 
     * @return bool True if format uses lossy compression
     */
    public function isLossy(): bool
    {
        return match($this) {
            self::JPEG, self::WEBP => true,
            self::PNG, self::GIF, self::TIFF, self::BMP, self::ICO => false
        };
    }

    /**
     * Check if this format is web-safe (displayable in browsers)
     * 
     * @return bool True if web-safe
     */
    public function isWebSafe(): bool
    {
        return match($this) {
            self::JPEG, self::PNG, self::GIF, self::WEBP, self::ICO => true,
            self::TIFF, self::BMP => false
        };
    }

    /**
     * Get the typical quality range for this format (if applicable)
     * 
     * @return array|null Array with [min, max, default] quality or null if N/A
     */
    public function getQualityRange(): ?array
    {
        return match($this) {
            self::JPEG => [0, 100, 85],
            self::WEBP => [0, 100, 80],
            self::PNG, self::GIF, self::TIFF, self::BMP, self::ICO => null
        };
    }

    /**
     * Get the file extensions associated with this format
     * 
     * @return array List of file extensions
     */
    public function getExtensions(): array
    {
        return match($this) {
            self::JPEG => ['jpg', 'jpeg', 'jpe'],
            self::PNG => ['png'],
            self::GIF => ['gif'],
            self::TIFF => ['tif', 'tiff'],
            self::BMP => ['bmp'],
            self::WEBP => ['webp'],
            self::ICO => ['ico']
        };
    }

    /**
     * Get the GD image type constant for this format
     * 
     * @return int|null GD IMAGETYPE constant or null if not supported
     */
    public function getGdImageType(): ?int
    {
        return match($this) {
            self::JPEG => IMAGETYPE_JPEG,
            self::PNG => IMAGETYPE_PNG,
            self::GIF => IMAGETYPE_GIF,
            self::TIFF => IMAGETYPE_TIFF_II,
            self::BMP => IMAGETYPE_BMP,
            self::WEBP => IMAGETYPE_WEBP ?? null,
            self::ICO => IMAGETYPE_ICO ?? null
        };
    }

    /**
     * Check if this format is supported by GD extension
     * 
     * @return bool True if GD supports this format
     */
    public function isSupportedByGd(): bool
    {
        if (!extension_loaded('gd')) {
            return false;
        }
        
        $info = gd_info();
        return match($this) {
            self::JPEG => $info['JPEG Support'] ?? false,
            self::PNG => $info['PNG Support'] ?? false,
            self::GIF => ($info['GIF Read Support'] ?? false) || ($info['GIF Create Support'] ?? false),
            self::TIFF => false, // GD doesn't reliably support TIFF
            self::BMP => version_compare(PHP_VERSION, '7.2.0') >= 0,
            self::WEBP => $info['WebP Support'] ?? false,
            self::ICO => false // GD doesn't support ICO
        };
    }

    /**
     * Get recommended usage for this format
     * 
     * @return string Usage recommendation
     */
    public function getRecommendedUsage(): string
    {
        return match($this) {
            self::JPEG => 'Photos and complex images with many colors',
            self::PNG => 'Images with transparency or simple graphics',
            self::GIF => 'Simple animations or images with few colors',
            self::TIFF => 'Professional/print images requiring lossless quality',
            self::BMP => 'Windows applications and legacy systems',
            self::WEBP => 'Modern web images with better compression',
            self::ICO => 'Application icons and favicons'
        };
    }

    /**
     * Create ImageFormat from file extension
     * 
     * @param string $extension File extension
     * @return ImageFormat|null ImageFormat instance or null if not found
     */
    public static function fromExtension(string $extension): ?self
    {
        $extension = strtolower(trim($extension, '.'));
        
        foreach (self::cases() as $format) {
            if (in_array($extension, $format->getExtensions())) {
                return $format;
            }
        }
        
        return null;
    }

    /**
     * Create ImageFormat from MIME type
     * 
     * @param string $mimeType MIME type string
     * @return ImageFormat|null ImageFormat instance or null if not found
     */
    public static function fromMimeType(string $mimeType): ?self
    {
        foreach (self::cases() as $format) {
            if ($format->getMimeType() === $mimeType) {
                return $format;
            }
        }
        
        return null;
    }

    /**
     * Get all web-safe image formats
     * 
     * @return array Array of web-safe ImageFormat cases
     */
    public static function getWebSafeFormats(): array
    {
        return array_filter(self::cases(), fn($format) => $format->isWebSafe());
    }
}