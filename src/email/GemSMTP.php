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
    private ?string $password = null;
    private string $senderName;
    
    // Default values as class constants
    private const DEFAULT_SMTP_PORT = 465;
    private const DEFAULT_TIMEOUT = 10;
    private const DEFAULT_LANGUAGE = 'de';
    private const MAX_SUBJECT_LENGTH = 998; // RFC 2822 limit
    
    private const ALLOWED_LANGUAGES = [
        'ar', 'az', 'ba', 'bg', 'bs', 'ca', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 
        'fa', 'fi', 'fo', 'fr', 'he', 'hi', 'hr', 'hu', 'hy', 'id', 'it', 'ja', 'ka', 
        'ko', 'lt', 'lv', 'ms', 'nb', 'nl', 'pl', 'pt', 'pt_br', 'ro', 'ru', 'sk', 
        'sl', 'sr', 'sv', 'tl', 'tr', 'uk', 'vi', 'zh', 'zh_cn'
    ];

    /**
     * Initialize SMTP mailer with configuration
     *
     * @param string $emailAddress SMTP email address (used as username)
     * @param string $password SMTP password
     * @param string $senderName Name to display as sender
     * @param bool $debug Enable debug mode
     * @param ?int $port SMTP port
     * @param ?int $timeout SMTP timeout
     * @throws Exception When SMTP connection fails or email format is invalid
     */
    public function __construct(
        string $emailAddress,
        string $password,
        string $senderName,
        bool $debug = false,
        ?int $port = null,
        ?int $timeout = null
    ) {
        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address format for SMTP username");
        }

        $this->username = $emailAddress;
        $this->password = $password;
        $this->senderName = $senderName;
        
        try {
            $this->initializeMailer(
                $debug,
                $port ?? self::DEFAULT_SMTP_PORT,
                $timeout ?? self::DEFAULT_TIMEOUT
            );
            $this->clearSensitiveData();
        } catch (Exception $e) {
            $this->clearSensitiveData();
            throw $e;
        }
    }

    /**
     * Initialize PHPMailer with SMTP settings
     */
    private function initializeMailer(bool $debug, int $port, int $timeout): void
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
        
        // Configurable port and timeout
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mail->Port = $port;
        $this->mail->Timeout = $timeout;
        
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
     * Clear sensitive data from memory
     */
    private function clearSensitiveData(): void
    {
        $this->password = null;
        
        // Overwrite with random data before nulling
        if (function_exists('sodium_memzero')) {
            sodium_memzero($this->password);
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
        string $contentLanguage = self::DEFAULT_LANGUAGE
    ): bool {
        $this->readyToSend = false;
        $this->error = null;

        try {
            if (!in_array($contentLanguage, self::ALLOWED_LANGUAGES, true)) {
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
     * Add embedded image
     *
     * @param string $imagePath Path to image file
     * @param string $cid Content ID to use in HTML (e.g., 'logo' for <img src="cid:logo">)
     * @return bool Success status
     */
    public function addEmbeddedImage(string $imagePath, string $cid): bool
    {
        try {
            if (!file_exists($imagePath)) {
                throw new Exception("Image file not found: {$imagePath}");
            }
            
            if (!getimagesize($imagePath)) {
                throw new Exception("Invalid image file: {$imagePath}");
            }
            
            $this->mail->addEmbeddedImage($imagePath, $cid);
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

    private function validateContent(string $htmlContent): bool
    {
        $size = strlen($htmlContent);
        if ($size > 26214400) { // 25MB limit
            throw new Exception("Email content exceeds maximum size limit of 25MB");
        }
        return true;
    }

    private function validateSubject(string $subject): string
    {
        $subject = trim($subject);
        if (empty($subject)) {
            throw new Exception("Email subject cannot be empty");
        }
        if (strlen($subject) > self::MAX_SUBJECT_LENGTH) {
            throw new Exception("Email subject exceeds maximum length of " . self::MAX_SUBJECT_LENGTH);
        }
        return $subject;
    }
}
