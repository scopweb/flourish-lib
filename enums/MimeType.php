<?php
/**
 * MIME type enumeration for file type identification
 * 
 * Provides type safety for MIME types supported by Flourish Framework.
 * Each MIME type includes category classification and validation methods.
 * 
 * @since PHP 8.4
 */
enum MimeType: string 
{
    // Image types
    case ImageTiff = 'image/tiff';
    case ImagePng = 'image/png';
    case ImageGif = 'image/gif';
    case ImageBmp = 'image/x-ms-bmp';
    case ImageJpeg = 'image/jpeg';
    case ImageIcon = 'application/vnd.microsoft.icon';
    case ImageSvg = 'image/svg+xml';
    
    // Video types
    case VideoQuicktime = 'video/quicktime';
    case VideoMp4 = 'video/mp4';
    case VideoWmv = 'video/x-ms-wmv';
    case VideoAsf = 'video/x-ms-asf';
    case VideoAvi = 'video/x-msvideo';
    case VideoFlv = 'video/x-flv';
    case VideoOgg = 'video/ogg';
    
    // Audio types
    case AudioMp4 = 'audio/mp4';
    case AudioMpeg = 'audio/mpeg';
    case AudioWma = 'audio/x-ms-wma';
    case AudioWav = 'audio/x-wav';
    case AudioVorbis = 'audio/vorbis';
    case AudioFlac = 'audio/x-flac';
    
    // Document types
    case ApplicationPostscript = 'application/postscript';
    case ApplicationPdf = 'application/pdf';
    case ApplicationMsword = 'application/msword';
    case ApplicationExcel = 'application/vnd.ms-excel';
    case ApplicationPowerpoint = 'application/vnd.ms-powerpoint';
    case TextRtf = 'text/rtf';
    
    // Text types
    case TextPlain = 'text/plain';
    case TextHtml = 'text/html';
    case TextCss = 'text/css';
    case TextCsv = 'text/csv';
    case TextXml = 'application/xml';
    case TextJson = 'application/json';
    case TextJavascript = 'application/javascript';
    
    // Archive types
    case ApplicationZip = 'application/zip';
    case ApplicationGzip = 'application/x-gzip';
    case ApplicationRar = 'application/x-rar-compressed';
    case ApplicationTar = 'application/x-tar';
    case ApplicationBzip2 = 'application/x-bzip2';
    
    // Binary types
    case ApplicationOctetStream = 'application/octet-stream';

    /**
     * Get the main category of this MIME type
     * 
     * @return string Main category (image, video, audio, text, application)
     */
    public function getCategory(): string
    {
        return match(true) {
            str_starts_with($this->value, 'image/') => 'image',
            str_starts_with($this->value, 'video/') => 'video',
            str_starts_with($this->value, 'audio/') => 'audio',
            str_starts_with($this->value, 'text/') => 'text',
            str_starts_with($this->value, 'application/') => 'application',
            default => 'unknown'
        };
    }

    /**
     * Check if this is an image MIME type
     * 
     * @return bool True if image type
     */
    public function isImage(): bool
    {
        return $this->getCategory() === 'image';
    }

    /**
     * Check if this is a video MIME type
     * 
     * @return bool True if video type
     */
    public function isVideo(): bool
    {
        return $this->getCategory() === 'video';
    }

    /**
     * Check if this is an audio MIME type
     * 
     * @return bool True if audio type
     */
    public function isAudio(): bool
    {
        return $this->getCategory() === 'audio';
    }

    /**
     * Check if this is a text MIME type
     * 
     * @return bool True if text type
     */
    public function isText(): bool
    {
        return $this->getCategory() === 'text';
    }

    /**
     * Check if this is a document MIME type
     * 
     * @return bool True if document type
     */
    public function isDocument(): bool
    {
        return match($this) {
            self::ApplicationPdf, self::ApplicationMsword, 
            self::ApplicationExcel, self::ApplicationPowerpoint, 
            self::TextRtf => true,
            default => false
        };
    }

    /**
     * Check if this is an archive MIME type
     * 
     * @return bool True if archive type
     */
    public function isArchive(): bool
    {
        return match($this) {
            self::ApplicationZip, self::ApplicationGzip, 
            self::ApplicationRar, self::ApplicationTar, 
            self::ApplicationBzip2 => true,
            default => false
        };
    }

    /**
     * Check if this MIME type is web-safe (displayable in browsers)
     * 
     * @return bool True if web-safe
     */
    public function isWebSafe(): bool
    {
        return match($this) {
            self::ImagePng, self::ImageJpeg, self::ImageGif, self::ImageSvg,
            self::TextHtml, self::TextCss, self::TextJavascript, self::TextPlain,
            self::ApplicationPdf, self::VideoMp4, self::AudioMpeg => true,
            default => false
        };
    }

    /**
     * Get typical file extension for this MIME type
     * 
     * @return string File extension without dot
     */
    public function getTypicalExtension(): string
    {
        return match($this) {
            self::ImageTiff => 'tiff',
            self::ImagePng => 'png',
            self::ImageGif => 'gif',
            self::ImageBmp => 'bmp',
            self::ImageJpeg => 'jpg',
            self::ImageIcon => 'ico',
            self::ImageSvg => 'svg',
            self::VideoQuicktime => 'mov',
            self::VideoMp4 => 'mp4',
            self::VideoWmv => 'wmv',
            self::VideoAvi => 'avi',
            self::AudioMp4 => 'm4a',
            self::AudioMpeg => 'mp3',
            self::AudioWav => 'wav',
            self::ApplicationPdf => 'pdf',
            self::ApplicationZip => 'zip',
            self::TextPlain => 'txt',
            self::TextHtml => 'html',
            self::TextCss => 'css',
            self::TextJavascript => 'js',
            self::TextJson => 'json',
            default => 'bin'
        };
    }

    /**
     * Get all MIME types by category
     * 
     * @param string $category Category name
     * @return array Array of MimeType cases for the category
     */
    public static function getByCategory(string $category): array
    {
        return array_filter(self::cases(), fn($type) => $type->getCategory() === $category);
    }

    /**
     * Create MimeType from string value with validation
     * 
     * @param string $mimeType MIME type string
     * @return MimeType|null MimeType instance or null if not found
     */
    public static function fromString(string $mimeType): ?self
    {
        return self::tryFrom($mimeType);
    }
}