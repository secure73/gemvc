<?php

namespace Gemvc\Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Gemvc\Http\Response;

final class GemSMTP
{
    private ?string $error = null;
    private bool $readyToSend = false;
    private PHPMailer $mail;
    private string $username;
    private ?string $password = null;
    private string $senderName;
    private string $defaultLanguage;
    private int $maxSubjectLength;
    private string $smtpHost;
    private const MAX_FILE_SIZE = 10485760; // 10MB in bytes
    private const MAX_CONTENT_SIZE = 26214400; // 25MB in bytes
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 2; // seconds
    
    private const ALLOWED_LANGUAGES = [
        'ar', 'az', 'ba', 'bg', 'bs', 'ca', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 
        'fa', 'fi', 'fo', 'fr', 'he', 'hi', 'hr', 'hu', 'hy', 'id', 'it', 'ja', 'ka', 
        'ko', 'lt', 'lv', 'ms', 'nb', 'nl', 'pl', 'pt', 'pt_br', 'ro', 'ru', 'sk', 
        'sl', 'sr', 'sv', 'tl', 'tr', 'uk', 'vi', 'zh', 'zh_cn'
    ];

    private const CONTENT_BLACKLIST = [
        '<script', 'javascript:', 'vbscript:', 'data:', 'onclick', 'onerror', 'onload',
        'expression(', 'url(', 'eval(', 'alert('
    ];

    private bool $verifySSL = true;  // Default to strict SSL
    private array $sslOptions = [];

    public function __construct(
        string $emailAddress,
        string $password,
        string $senderName,
        string $smtpHost,
        int $port,
        int $timeout = 10,
        bool $debug = false,
        string $defaultLanguage = 'de',
        int $maxSubjectLength = 998
    ) {
        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            Response::internalError("Invalid email address format for SMTP username")->show();
            die();
        }

        $this->username = $emailAddress;
        $this->password = $password;
        $this->senderName = $senderName;
        $this->defaultLanguage = $defaultLanguage;
        $this->maxSubjectLength = $maxSubjectLength;
        $this->smtpHost = $smtpHost;
        
        try {
            $this->initializeMailer(
                $debug,
                $port,
                $timeout
            );
            $this->clearSensitiveData();
        } catch (\Exception $e) {
            $this->clearSensitiveData();
            throw $e;
        }
    }

    private function initializeMailer(bool $debug, int $port, int $timeout): void
    {
        $this->mail = new PHPMailer(true);
        
        // Set debug level using SMTP class constants
        $this->mail->SMTPDebug = $debug ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
        $this->mail->Debugoutput = 'error_log';  // Log debug output to error log
        
        // Force SMTP with strict settings
        $this->mail->isSMTP();
        $this->mail->Host = $this->smtpHost;
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $this->username;
        $this->mail->Password = $this->password;
        
        // Set encryption based on port
        if ($port === 465) {
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($port === 587) {
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            Response::internalError("Only ports 465 (SMTPS) and 587 (STARTTLS) are supported")->show();
            die();
        }
        
        $this->mail->Port = $port;
        $this->mail->Timeout = $timeout;
        
        // Force HTML and UTF-8
        $this->mail->isHTML(true);
        $this->mail->CharSet = PHPMailer::CHARSET_UTF8;
        $this->mail->Encoding = PHPMailer::ENCODING_BASE64;

        // Use configured SSL options or default to strict if not set
        $sslOptions = !empty($this->sslOptions) ? $this->sslOptions : [
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

        // Connection with retries
        for ($i = 0; $i < self::MAX_RETRIES; $i++) {
            try {
                if (!$this->mail->smtpConnect($sslOptions)) {
                    if ($i === self::MAX_RETRIES - 1) {
                        Response::internalError("SMTP connection failed after " . self::MAX_RETRIES . " attempts")->show();
                        die();
                    }
                    sleep(self::RETRY_DELAY);
                    continue;
                }
                return;
            } catch (\Exception $e) {
                if ($i === self::MAX_RETRIES - 1) {
                    Response::internalError("Secure SMTP Connection Error after " . self::MAX_RETRIES . " attempts: " . $e->getMessage())->show();
                    die();
                }
                sleep(self::RETRY_DELAY);
            }
        }
    }

    private function clearSensitiveData(): void
    {
        if (isset($this->mail)) {
            $this->mail->Password = null;
        }
        
        if (function_exists('sodium_memzero')) {
            if ($this->password !== null) {
                sodium_memzero($this->password);
            }
        }
        $this->password = null;
    }

    public function createMail(
        string $receiverEmail,
        string $receiverName,
        string $subject,
        string $htmlContent,
        string $contentLanguage = null
    ): bool {
        $this->readyToSend = false;
        $this->error = null;

        // Stricter content type validation
        if (!str_contains($htmlContent, '<html') || !str_contains($htmlContent, '</html>')) {
            Response::internalError("Content must be valid HTML with <html> tags")->show();
            die();
        }

        if (!in_array($contentLanguage, self::ALLOWED_LANGUAGES, true)) {
            Response::internalError("Invalid language. Allowed: " . implode(', ', self::ALLOWED_LANGUAGES))->show();
            die();
        }

        if (!filter_var($this->username, FILTER_VALIDATE_EMAIL)) {
            Response::internalError("Invalid sender email format")->show();
            die();
        }

        if (!filter_var($receiverEmail, FILTER_VALIDATE_EMAIL)) {
            Response::internalError("Invalid receiver email format")->show();
            die();
        }

        $this->validateContent($htmlContent);
        $subject = $this->validateSubject($subject);

        $this->mail->setLanguage($contentLanguage ?? $this->defaultLanguage);
        $this->mail->setFrom($this->username, $this->senderName);
        $this->mail->addAddress($receiverEmail, $receiverName);
        $this->mail->Subject = $subject;
        $this->mail->Body = $this->sanitizeHtml($htmlContent);
        $this->mail->AltBody = strip_tags($htmlContent); // Always provide plain text alternative
        
        $this->readyToSend = true;
        return true;
    }

    private function sanitizeHtml(string $html): string
    {
        if (!mb_check_encoding($html, 'UTF-8')) {
            Response::internalError("Invalid UTF-8 encoding in content")->show();
            die();
        }

        $html = htmlspecialchars($html, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
        
        // Additional sanitization
        $html = preg_replace('/<!--(.|\s)*?-->/', '', $html); // Remove comments
        return $html;
    }

    private function validateContent(string $htmlContent): void
    {
        $size = strlen($htmlContent);
        if ($size > self::MAX_CONTENT_SIZE) {
            Response::internalError("Email content exceeds maximum size limit of 25MB")->show();
            die();
        }
        
        if (!mb_check_encoding($htmlContent, 'UTF-8')) {
            Response::internalError("Invalid UTF-8 encoding in content")->show();
            die();
        }
        
        foreach (self::CONTENT_BLACKLIST as $term) {
            if (stripos($htmlContent, $term) !== false) {
                Response::internalError("Potentially unsafe content detected")->show();
                die();
            }
        }
    }

    public function addAttachment(string $filePath, ?string $showName = null): bool
    {
        if (!file_exists($filePath)) {
            Response::internalError("Attachment file not found: {$filePath}")->show();
            die();
        }
        
        // Path traversal protection
        $realPath = realpath($filePath);
        if ($realPath === false || !str_starts_with($realPath, realpath(getcwd()))) {
            Response::internalError("Invalid file path")->show();
            die();
        }
        
        if (filesize($filePath) > self::MAX_FILE_SIZE) {
            Response::internalError("File size exceeds maximum allowed size of 10MB")->show();
            die();
        }

        $mimeType = mime_content_type($filePath);
        if ($mimeType === false) {
            Response::internalError("Could not determine file type")->show();
            die();
        }

        try {
            $this->mail->addAttachment($filePath, $showName ?? basename($filePath));
            return true;
        } catch (\Exception $e) {
            Response::internalError("Failed to add attachment: " . $e->getMessage())->show();
            die();
        }
    }

    public function addEmbeddedImage(string $imagePath, string $cid): bool
    {
        if (!file_exists($imagePath)) {
            Response::internalError("Image file not found: {$imagePath}")->show();
            die();
        }
        
        // Path traversal protection
        $realPath = realpath($imagePath);
        if ($realPath === false || !str_starts_with($realPath, realpath(getcwd()))) {
            Response::internalError("Invalid image path")->show();
            die();
        }
        
        if (!getimagesize($imagePath)) {
            Response::internalError("Invalid image file: {$imagePath}")->show();
            die();
        }
        
        if (filesize($imagePath) > self::MAX_FILE_SIZE) {
            Response::internalError("Image size exceeds maximum allowed size of 10MB")->show();
            die();
        }
        
        try {
            $this->mail->addEmbeddedImage($imagePath, $cid);
            return true;
        } catch (\Exception $e) {
            Response::internalError("Failed to add embedded image: " . $e->getMessage())->show();
            die();
        }
    }

    private function ensureValidConnection(): void
    {
        if (!$this->mail->smtpConnect()) {
            $this->mail->smtpClose();
            Response::internalError("SMTP connection lost")->show();
            die();
        }
    }

    public function send(): bool
    {
        if (!$this->readyToSend) {
            Response::internalError("Email not properly configured. Call createMail() first.")->show();
            die();
        }

        try {
            $this->ensureValidConnection();
            $result = $this->mail->send();
            if (!$result) {
                Response::internalError("Email sending failed: " . $this->mail->ErrorInfo)->show();
                die();
            }
            return true;
        } catch (\Exception $e) {
            Response::internalError("Email sending failed: " . $e->getMessage())->show();
            die();
        }
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function reset(): void
    {
        if (isset($this->mail)) {
            $this->mail->clearAllRecipients();
            $this->mail->clearAttachments();
            $this->mail->clearCustomHeaders();
            $this->mail->clearReplyTos();
        }
        $this->readyToSend = false;
        $this->error = null;
    }

    private function validateSubject(string $subject): string
    {
        $subject = trim($subject);
        if (empty($subject)) {
            Response::internalError("Email subject cannot be empty")->show();
            die();
        }
        if (strlen($subject) > $this->maxSubjectLength) {
            Response::internalError("Email subject exceeds maximum length of " . $this->maxSubjectLength)->show();
            die();
        }
        return $subject;
    }

    public function __destruct()
    {
        $this->reset();
        $this->clearSensitiveData();
        if (isset($this->mail)) {
            $this->mail->smtpClose();
        }
    }

    /**
     * Set SSL verification options
     * @param bool $verify Enable/disable SSL verification
     * @param bool $allowSelfSigned Allow self-signed certificates
     * @param bool $verifyPeerName Verify peer name
     * @return void
     */
    public function setSSLVerification(
        bool $verify = true, 
        bool $allowSelfSigned = false,
        bool $verifyPeerName = true
    ): void {
        $this->verifySSL = $verify;
        $this->sslOptions = [
            'ssl' => [
                'verify_peer' => $verify,
                'verify_peer_name' => $verify && $verifyPeerName,
                'allow_self_signed' => !$verify || $allowSelfSigned,
                'min_tls_version' => 'TLSv1.2',
                'disable_compression' => true,
                'SNI_enabled' => true,
                'verify_depth' => 5,
            ]
        ];
    }
}
