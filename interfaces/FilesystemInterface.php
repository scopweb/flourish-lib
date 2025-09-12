<?php
/**
 * Filesystem interface for dependency injection
 * 
 * Provides a contract for filesystem operations in Flourish Framework.
 * Enables testing with mock filesystems and abstraction of file operations.
 * 
 * @since PHP 8.4
 */
interface FilesystemInterface
{
    /**
     * Read the contents of a file
     *
     * @param string $path File path to read
     * @return string File contents
     * @throws FilesystemException If file cannot be read
     */
    public function read(string $path): string;

    /**
     * Write content to a file
     *
     * @param string $path File path to write
     * @param string $content Content to write
     * @param bool $append Whether to append or overwrite
     * @return bool True on success
     */
    public function write(string $path, string $content, bool $append = false): bool;

    /**
     * Check if a file or directory exists
     *
     * @param string $path Path to check
     * @return bool True if exists
     */
    public function exists(string $path): bool;

    /**
     * Delete a file
     *
     * @param string $path File path to delete
     * @return bool True on success
     */
    public function delete(string $path): bool;

    /**
     * Copy a file
     *
     * @param string $source Source file path
     * @param string $destination Destination file path
     * @param bool $overwrite Whether to overwrite existing file
     * @return bool True on success
     */
    public function copy(string $source, string $destination, bool $overwrite = false): bool;

    /**
     * Move/rename a file
     *
     * @param string $source Source file path
     * @param string $destination Destination file path
     * @param bool $overwrite Whether to overwrite existing file
     * @return bool True on success
     */
    public function move(string $source, string $destination, bool $overwrite = false): bool;

    /**
     * Get file size in bytes
     *
     * @param string $path File path
     * @return int File size in bytes
     */
    public function size(string $path): int;

    /**
     * Get file modification time
     *
     * @param string $path File path
     * @return int Unix timestamp
     */
    public function lastModified(string $path): int;

    /**
     * Check if path is a file
     *
     * @param string $path Path to check
     * @return bool True if it's a file
     */
    public function isFile(string $path): bool;

    /**
     * Check if path is a directory
     *
     * @param string $path Path to check
     * @return bool True if it's a directory
     */
    public function isDirectory(string $path): bool;

    /**
     * Create a directory
     *
     * @param string $path Directory path to create
     * @param int $permissions Directory permissions (octal)
     * @param bool $recursive Create parent directories
     * @return bool True on success
     */
    public function createDirectory(string $path, int $permissions = 0755, bool $recursive = false): bool;

    /**
     * Delete a directory
     *
     * @param string $path Directory path to delete
     * @param bool $recursive Delete recursively
     * @return bool True on success
     */
    public function deleteDirectory(string $path, bool $recursive = false): bool;

    /**
     * List directory contents
     *
     * @param string $path Directory path
     * @param bool $recursive List recursively
     * @return array Array of file/directory paths
     */
    public function listDirectory(string $path, bool $recursive = false): array;

    /**
     * Get file permissions
     *
     * @param string $path File path
     * @return int File permissions (octal)
     */
    public function getPermissions(string $path): int;

    /**
     * Set file permissions
     *
     * @param string $path File path
     * @param int $permissions New permissions (octal)
     * @return bool True on success
     */
    public function setPermissions(string $path, int $permissions): bool;

    /**
     * Check if file/directory is readable
     *
     * @param string $path Path to check
     * @return bool True if readable
     */
    public function isReadable(string $path): bool;

    /**
     * Check if file/directory is writable
     *
     * @param string $path Path to check
     * @return bool True if writable
     */
    public function isWritable(string $path): bool;

    /**
     * Get file MIME type
     *
     * @param string $path File path
     * @return string MIME type
     */
    public function getMimeType(string $path): string;

    /**
     * Create a temporary file
     *
     * @param string $prefix Filename prefix
     * @param string $suffix Filename suffix
     * @return string Temporary file path
     */
    public function createTemporaryFile(string $prefix = '', string $suffix = ''): string;

    /**
     * Get the absolute path
     *
     * @param string $path Input path
     * @return string Absolute path
     */
    public function realPath(string $path): string;
}