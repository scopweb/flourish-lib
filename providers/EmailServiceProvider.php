<?php
/**
 * Email Service Provider for Flourish Framework
 * 
 * Registers email services in the dependency injection container,
 * supporting SMTP, mail() function, and modern email handling.
 * 
 * @since PHP 8.4
 */

require_once __DIR__ . '/../container/Container.php';
require_once __DIR__ . '/../interfaces/EmailServiceInterface.php';
require_once __DIR__ . '/../lib/fEmail.php';
require_once __DIR__ . '/../lib/fSMTP.php';

class EmailServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        // Register main email service
        $container->bind('email', function(Container $c) {
            $config = $c->get('config');
            $email = new fEmail();
            
            // Configure default settings
            if ($config->has('email.from_email')) {
                $email->setFromEmail($config->get('email.from_email'));
            }
            
            if ($config->has('email.from_name')) {
                $email->setFromName($config->get('email.from_name'));
            }
            
            return $email;
        });
        
        // Alias for PSR interface
        $container->alias(EmailServiceInterface::class, 'email');
        
        // Register SMTP transport
        $container->bind('email.transport.smtp', function(Container $c) {
            $config = $c->get('config');
            
            if (!$config->has('smtp.host')) {
                throw new ContainerException('SMTP host not configured');
            }
            
            $smtp = new fSMTP(
                $config->get('smtp.host'),
                $config->get('smtp.port', 587),
                $config->get('smtp.secure', false),
                $config->get('smtp.timeout', 10)
            );
            
            if ($config->has('smtp.username') && $config->has('smtp.password')) {
                $smtp->authenticate(
                    $config->get('smtp.username'),
                    $config->get('smtp.password')
                );
            }
            
            return $smtp;
        });
        
        // Register mail transport (PHP mail() function)
        $container->bind('email.transport.mail', function(Container $c) {
            return new class implements EmailTransportInterface {
                private ?string $lastError = null;
                
                public function sendMessage(EmailMessageInterface $message): bool {
                    $headers = $this->buildHeaders($message);
                    $body = $message->getBody();
                    $subject = $message->getSubject();
                    $to = $this->formatRecipients($message->getTo());
                    
                    $result = mail($to, $subject, $body, $headers);
                    
                    if (!$result) {
                        $this->lastError = 'Failed to send email using mail() function';
                    }
                    
                    return $result;
                }
                
                public function getLastError(): ?string {
                    return $this->lastError;
                }
                
                public function configure(array $config): void {
                    // Mail transport doesn't require configuration
                }
                
                private function buildHeaders(EmailMessageInterface $message): string {
                    $headers = [];
                    
                    $from = $message->getFrom();
                    if (!empty($from)) {
                        $headers[] = 'From: ' . $this->formatRecipients($from);
                    }
                    
                    $cc = $message->getCC();
                    if (!empty($cc)) {
                        $headers[] = 'Cc: ' . $this->formatRecipients($cc);
                    }
                    
                    $bcc = $message->getBCC();
                    if (!empty($bcc)) {
                        $headers[] = 'Bcc: ' . $this->formatRecipients($bcc);
                    }
                    
                    $headers[] = 'Content-Type: ' . $message->getBodyType() . '; charset=UTF-8';
                    $headers[] = 'MIME-Version: 1.0';
                    
                    foreach ($message->getHeaders() as $name => $value) {
                        $headers[] = $name . ': ' . $value;
                    }
                    
                    return implode("\r\n", $headers);
                }
                
                private function formatRecipients(array $recipients): string {
                    $formatted = [];
                    foreach ($recipients as $email => $name) {
                        if (is_numeric($email)) {
                            $formatted[] = $name;
                        } else {
                            $formatted[] = $name ? "$name <$email>" : $email;
                        }
                    }
                    return implode(', ', $formatted);
                }
            };
        });
        
        // Register email factory for quick sending
        $container->bind('email.factory', function(Container $c) {
            return function() use ($c) {
                return clone $c->get('email');
            };
        });
        
        // Register email queue for batch processing
        $container->bind('email.queue', function(Container $c) {
            return new class {
                private array $queue = [];
                
                public function add(EmailMessageInterface $message): void {
                    $this->queue[] = $message;
                }
                
                public function process(EmailServiceInterface $service): array {
                    $results = [];
                    foreach ($this->queue as $index => $message) {
                        $results[$index] = $service->send($message);
                    }
                    $this->queue = []; // Clear queue after processing
                    return $results;
                }
                
                public function count(): int {
                    return count($this->queue);
                }
                
                public function clear(): void {
                    $this->queue = [];
                }
            };
        });
    }

    public function boot(Container $container): void
    {
        // Configure email service with transport
        if ($container->has('config') && $container->has('email')) {
            $config = $container->get('config');
            $email = $container->get('email');
            
            // Set transport based on configuration
            $transportType = $config->get('email.transport', 'mail');
            
            switch ($transportType) {
                case 'smtp':
                    if ($container->has('email.transport.smtp')) {
                        $transport = $container->get('email.transport.smtp');
                        $email->setTransport($transport);
                    }
                    break;
                    
                case 'mail':
                    if ($container->has('email.transport.mail')) {
                        $transport = $container->get('email.transport.mail');
                        $email->setTransport($transport);
                    }
                    break;
            }
            
            // Enable debugging if configured
            if ($config->get('email.debug', false)) {
                $email->enableDebugging(true);
            }
        }
    }
}