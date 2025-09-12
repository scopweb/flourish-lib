<?php
/**
 * Filesystem Service Provider for Flourish Framework
 * 
 * Registers filesystem services in the dependency injection container,
 * supporting file operations, directory management, and upload handling.
 * 
 * @since PHP 8.4
 */

require_once __DIR__ . '/../container/Container.php';
require_once __DIR__ . '/../interfaces/FilesystemInterface.php';
require_once __DIR__ . '/../lib/fFilesystem.php';
require_once __DIR__ . '/../lib/fFile.php';
require_once __DIR__ . '/../lib/fDirectory.php';
require_once __DIR__ . '/../lib/fUpload.php';

class FilesystemServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        // Register main filesystem service
        $container->bind('filesystem', function(Container $c) {
            return new fFilesystem();
        }, true); // Singleton
        
        // Alias for PSR interface
        $container->alias(FilesystemInterface::class, 'filesystem');
        
        // Register file factory
        $container->bind('file.factory', function(Container $c) {
            return function(string $path) {
                return new fFile($path);
            };
        });
        
        // Register directory factory
        $container->bind('directory.factory', function(Container $c) {
            return function(string $path) {
                return new fDirectory($path);
            };
        });
        
        // Register upload handler
        $container->bind('upload', function(Container $c) {
            $config = $c->get('config');
            
            $upload = new fUpload();
            
            // Configure upload settings
            if ($config->has('upload.directory')) {
                $upload->setDirectory($config->get('upload.directory'));
            }
            
            if ($config->has('upload.max_size')) {
                $upload->setMaxSize($config->get('upload.max_size'));
            }
            
            if ($config->has('upload.allowed_types')) {
                $upload->setAllowedMIMETypes($config->get('upload.allowed_types'));
            }
            
            return $upload;
        });
        
        // Register image processor
        $container->bind('image.processor', function(Container $c) {
            require_once __DIR__ . '/../lib/fImage.php';
            
            return new class {
                public function process(string $path): fImage {
                    return new fImage($path);
                }
                
                public function resize(string $inputPath, string $outputPath, int $width, int $height): bool {
                    try {
                        $image = new fImage($inputPath);
                        $image->resize($width, $height);
                        $image->saveChanges($outputPath);
                        return true;
                    } catch (Exception $e) {
                        return false;
                    }
                }
                
                public function crop(string $inputPath, string $outputPath, int $width, int $height, int $x = 0, int $y = 0): bool {
                    try {
                        $image = new fImage($inputPath);
                        $image->crop($width, $height, $x, $y);
                        $image->saveChanges($outputPath);
                        return true;
                    } catch (Exception $e) {
                        return false;
                    }
                }
            };
        });
        
        // Register file validator
        $container->bind('file.validator', function(Container $c) {
            $config = $c->get('config');
            
            return new class($config) {
                private ConfigInterface $config;
                
                public function __construct(ConfigInterface $config) {
                    $this->config = $config;
                }
                
                public function validateUpload(string $filePath): array {
                    $errors = [];
                    
                    if (!file_exists($filePath)) {
                        $errors[] = 'File does not exist';
                        return $errors;
                    }
                    
                    $file = new fFile($filePath);
                    
                    // Check file size
                    $maxSize = $this->config->get('validation.file.max_size', 5242880); // 5MB default
                    if ($file->getSize() > $maxSize) {
                        $errors[] = "File size exceeds maximum allowed size of " . number_format($maxSize / 1024 / 1024, 2) . " MB";
                    }
                    
                    // Check file type
                    $allowedTypes = $this->config->get('validation.file.allowed_types', ['image/jpeg', 'image/png', 'image/gif']);
                    if (!in_array($file->getMimeType(), $allowedTypes)) {
                        $errors[] = "File type '" . $file->getMimeType() . "' is not allowed";
                    }
                    
                    // Check file extension
                    $allowedExtensions = $this->config->get('validation.file.allowed_extensions', ['jpg', 'jpeg', 'png', 'gif']);
                    if (!in_array(strtolower($file->getExtension()), $allowedExtensions)) {
                        $errors[] = "File extension '" . $file->getExtension() . "' is not allowed";
                    }
                    
                    return $errors;
                }
                
                public function isValid(string $filePath): bool {
                    return empty($this->validateUpload($filePath));
                }
            };
        });
        
        // Register temporary file manager
        $container->bind('temp.manager', function(Container $c) {
            return new class {
                private array $tempFiles = [];
                
                public function create(string $prefix = 'tmp_', string $extension = ''): string {
                    $tempFile = tempnam(sys_get_temp_dir(), $prefix);
                    if ($extension) {
                        $newName = $tempFile . '.' . ltrim($extension, '.');
                        rename($tempFile, $newName);
                        $tempFile = $newName;
                    }
                    
                    $this->tempFiles[] = $tempFile;
                    return $tempFile;
                }
                
                public function cleanup(): void {
                    foreach ($this->tempFiles as $file) {
                        if (file_exists($file)) {
                            unlink($file);
                        }
                    }
                    $this->tempFiles = [];
                }
                
                public function __destruct() {
                    $this->cleanup();
                }
            };
        }, true);
    }

    public function boot(Container $container): void
    {
        // Set up filesystem permissions if configured
        if ($container->has('config')) {
            $config = $container->get('config');
            
            // Set default directory permissions
            if ($config->has('filesystem.default_dir_permissions')) {
                $permissions = $config->get('filesystem.default_dir_permissions');
                // Configure default permissions
            }
            
            // Set up upload directory
            if ($config->has('upload.directory')) {
                $uploadDir = $config->get('upload.directory');
                if (!is_dir($uploadDir)) {
                    $filesystem = $container->get('filesystem');
                    $filesystem->createDirectory($uploadDir, 0755, true);
                }
            }
            
            // Set up temp cleanup schedule
            if ($config->get('filesystem.auto_cleanup_temp', true)) {
                // Register cleanup tasks
                register_shutdown_function(function() use ($container) {
                    if ($container->has('temp.manager')) {
                        $container->get('temp.manager')->cleanup();
                    }
                });
            }
        }
    }
}