<?php
/**
 * Email service interface for dependency injection
 * 
 * Provides a contract for email services in Flourish Framework.
 * Supports both simple mail() function and SMTP transport.
 * 
 * @since PHP 8.4
 */
interface EmailServiceInterface
{
    /**
     * Send an email message
     *
     * @param EmailMessageInterface $message Email message to send
     * @return bool True on success
     */
    public function sendMessage(EmailMessageInterface $message): bool;

    /**
     * Set the email transport mechanism
     *
     * @param EmailTransportInterface $transport Transport to use
     * @return void
     */
    public function setTransport(EmailTransportInterface $transport): void;

    /**
     * Get the current transport
     *
     * @return EmailTransportInterface Current transport
     */
    public function getTransport(): EmailTransportInterface;

    /**
     * Get the last error message
     *
     * @return string|null Last error or null if no error
     */
    public function getLastError(): ?string;

    /**
     * Enable or disable debugging
     *
     * @param bool $flag Whether to enable debugging
     * @return self For method chaining
     */
    public function enableDebugging(bool $flag): self;

    /**
     * Set the sender information
     *
     * @param string $email Sender email
     * @param string|null $name Sender name
     * @return self For method chaining
     */
    public function setFrom(string $email, ?string $name = null): self;

    /**
     * Add a recipient
     *
     * @param string $email Recipient email
     * @param string|null $name Recipient name
     * @return self For method chaining
     */
    public function addTo(string $email, ?string $name = null): self;

    /**
     * Add a CC recipient
     *
     * @param string $email CC recipient email
     * @param string|null $name CC recipient name
     * @return self For method chaining
     */
    public function addCC(string $email, ?string $name = null): self;

    /**
     * Add a BCC recipient
     *
     * @param string $email BCC recipient email
     * @param string|null $name BCC recipient name
     * @return self For method chaining
     */
    public function addBCC(string $email, ?string $name = null): self;

    /**
     * Set email subject
     *
     * @param string $subject Email subject
     * @return self For method chaining
     */
    public function setEmailSubject(string $subject): self;

    /**
     * Set email body content
     *
     * @param string $body Email body
     * @param string $type Content type (text/plain, text/html)
     * @return self For method chaining
     */
    public function setBodyContent(string $body, string $type = 'text/plain'): self;

    /**
     * Add an attachment
     *
     * @param string $filePath Path to file to attach
     * @param string|null $name Custom attachment name
     * @param string|null $mimeType MIME type override
     * @return self For method chaining
     */
    public function attachFile(string $filePath, ?string $name = null, ?string $mimeType = null): self;

    /**
     * Reset the email service to default state
     *
     * @return void
     */
    public function resetEmail(): void;
}

/**
 * Email message interface
 */
interface EmailMessageInterface
{
    public function getFrom(): array;
    public function getTo(): array;
    public function getCC(): array;
    public function getBCC(): array;
    public function getSubject(): string;
    public function getBody(): string;
    public function getBodyType(): string;
    public function getAttachments(): array;
    public function getHeaders(): array;
}

/**
 * Email transport interface
 */
interface EmailTransportInterface
{
    public function sendMessage(EmailMessageInterface $message): bool;
    public function getLastError(): ?string;
    public function configure(array $config): void;
}