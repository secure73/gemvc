<?php

namespace Gemvc\Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Exception;

final class GemSMTP
{
    private ?string $error = null;
    private bool $readyToSend = false;
    private PHPMailer $mail;
    private string $username;
    private string $password;
    private string $senderName;
    
    private const ALLOWED_LANGUAGES = ['en', 'de', 'fa'];

    /**
     * Initialize SMTP mailer with configuration
     *
     * @param string $emailAddress SMTP email address (used as username)
     * @param string $password SMTP password
     * @param string $senderName Name to display as sender
     * @param bool $debug Enable debug mode
     * @throws Exception When SMTP connection fails or email format is invalid
     */
    public function __construct(
        string $emailAddress,
        string $password,
        string $senderName,
        bool $debug = false
    ) {
        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address format for SMTP username");
        }

        $this->username = $emailAddress;
        $this->password = $password;
        $this->senderName = $senderName;
        
        $this->initializeMailer($debug);
    }

    /**
     * Initialize PHPMailer with SMTP settings
     */
    private function initializeMailer(bool $debug): void
    {
        $this->mail = new PHPMailer(true);
        
        // Debug settings
        $this->mail->SMTPDebug = $debug ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
        $this->mail->Debugoutput = 'error_log';

        // SMTP configuration with enforced security
        $this->mail->isSMTP();
        $this->mail->Host = $this->getConfigValue('SMTP_HOST', 'localhost');
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $this->username;
        $this->mail->Password = $this->password;
        
        // Force SMTPS (TLS) encryption
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mail->Port = 465; // Standard SMTPS port
        
        // Force secure character set
        $this->mail->CharSet = PHPMailer::CHARSET_UTF8;
        
        // Force HTML email
        $this->mail->isHTML(true);

        // Enforce strict SSL/TLS settings
        $sslOptions = [
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'min_tls_version' => 'TLSv1.2',
                'disable_compression' => true,
                'SNI_enabled' => true,
                'verify_depth' => 5,
            ]
        ];

        try {
            $this->mail->smtpConnect($sslOptions);
        } catch (Exception $e) {
            throw new Exception("Secure SMTP Connection Error: " . $e->getMessage());
        }
    }

    /**
     * Create a new email
     *
     * @param string $receiverEmail Recipient email address
     * @param string $receiverName Recipient name
     * @param string $subject Email subject
     * @param string $htmlContent HTML email content
     * @param string $contentLanguage Language code (en, de, fa, etc.)
     * @return bool Success status
     */
    public function createMail(
        string $receiverEmail,
        string $receiverName,
        string $subject,
        string $htmlContent,
        string $contentLanguage = 'en'
    ): bool {
        $this->readyToSend = false;
        $this->error = null;

        try {
            if (!in_array($contentLanguage, self::ALLOWED_LANGUAGES)) {
                throw new Exception("Invalid language. Allowed: " . implode(', ', self::ALLOWED_LANGUAGES));
            }

            if (!filter_var($this->username, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid sender email format");
            }

            if (!filter_var($receiverEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid receiver email format");
            }

            $this->mail->setLanguage($contentLanguage);
            $this->mail->setFrom($this->username, $this->senderName);
            $this->mail->addAddress($receiverEmail, $receiverName);
            $this->mail->Subject = $subject;
            $this->mail->Body = $this->sanitizeHtml($htmlContent);
            
            $this->readyToSend = true;
            return true;

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Sanitize HTML content
     *
     * @param string $html HTML content
     * @return string Sanitized HTML
     */
    private function sanitizeHtml(string $html): string
    {
        // Basic XSS protection
        return htmlspecialchars($html, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
    }

    /**
     * Add CC recipient
     */
    public function addCC(string $email, ?string $receiverName = null): bool
    {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid CC email format");
            }
            
            $this->mail->addCC($email, $receiverName ?? $email);
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Add BCC recipient
     */
    public function addBCC(string $email, ?string $receiverName = null): bool
    {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid BCC email format");
            }
            
            $this->mail->addBCC($email, $receiverName ?? $email);
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Add file attachment
     */
    public function addAttachment(string $filePath, ?string $showName = null): bool
    {
        try {
            if (!file_exists($filePath)) {
                throw new Exception("Attachment file not found: {$filePath}");
            }
            
            $this->mail->addAttachment($filePath, $showName ?? basename($filePath));
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Send the email
     */
    public function send(): bool
    {
        if (!$this->readyToSend) {
            $this->error = "Email not properly configured. Call createMail() first.";
            return false;
        }

        try {
            return $this->mail->send();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Get the last error message
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Get configuration value with fallback
     */
    private function getConfigValue(string $key, string $default): string
    {
        return $_ENV[$key] ?? $default;
    }

    /**
     * Reset the mailer for reuse
     */
    public function reset(): void
    {
        $this->mail->clearAllRecipients();
        $this->mail->clearAttachments();
        $this->readyToSend = false;
        $this->error = null;
    }
}
