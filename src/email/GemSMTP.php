<?php

namespace Gemvc\Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Exception;

class GemSMTP
{
    public ?string $error;
    private bool $readyToSend = false;
    protected PHPMailer $mail;

    /**
     * Constructor
     *
     * @param int $smtpDebugLevel
     * this class is wrapper from PHPMailer
     * smtp debug level between 0 to 4 and it is tls enabled
     */
    public function __construct(string $username, string $password)
    {
        $this->mail = new PHPMailer(true);
        
        // Enable debugging
        $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $this->mail->Debugoutput = 'error_log';

        // SMTP configuration
        $this->mail->isSMTP();
        $this->mail->Host = $_ENV['SMTP_HOST'];        // mail.elexbo.de
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $username;    // noreply
        $this->mail->Password = $password;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mail->Port = $_ENV['SMTP_PORT'];        // 465
        
        // Add error handling
        try {
            $this->mail->smtpConnect([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
        } catch (Exception $e) {
            error_log("SMTP Connection Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @param  string $senderEmail
     * @param  string $senderName
     * @param  string $reciverEmail
     * @param  string $reciverName
     * @param  string $subject
     * @param  string $emailContent
     * @param  string $contentLanguage
     * @return bool
     * Tipp: for add Attachment you can use $this->addAttachment();
     * Tipp: you can use method $content  = $this->mail->$mail->msgHTML(file_get_contents('contents.html'), __DIR__);
     * and use result in this function $emailContent in case of wish to use  HTML Template file
     */
    public function createMail(string $senderEmail, string $senderName, string $reciverEmail, string $reciverName, string $subject, string $emailContent, string $contentLanguage): bool
    {
        if ($this->mail->setLanguage($contentLanguage)) {
            if (filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
                if ($this->mail->setFrom($senderEmail, $senderName)) {
                    if (filter_var($reciverEmail, FILTER_VALIDATE_EMAIL)) {
                        if ($this->addReciver($reciverEmail, $reciverName)) {
                            $this->mail->Subject = $subject;
                            $this->mail->Body = $emailContent;
                            $this->readyToSend = true;
                            $this->error = null;
                            return true;
                        }
                    } else {
                        $this->error = 'Invalid email format for reciverEmail';
                    }
                }
            } else {
                $this->error = 'invalid format for senderEmail';
            }
        } else {
            $this->error = 'Invalid Language: accept en, fa, de, ...';
        }
        return false;
    }

    public function addReciver(string $email, string $reciverName = null): bool
    {
        $reciverName = ($reciverName) ?: $email;
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->mail->addAddress($email, $reciverName);

            return true;
        }
        $this->error = "Given Email address: {$email} is wrong formatted";

        return false;
    }

    public function addCC(string $email, string $reciverName = null): bool
    {
        $reciverName = ($reciverName) ?: $email;
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->mail->addCC($email, $reciverName);

            return true;
        }
        $this->error = "Given CC Email address: {$email} is wrong formatted";

        return false;
    }

    public function addBCC(string $email, string $reciverName = null): bool
    {
        $reciverName = ($reciverName) ?: $email;
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->mail->addBCC($email, $reciverName);

            return true;
        }
        $this->error = "Given BCC Email address: {$email} is wrong formatted";

        return false;
    }

    public function addAttachment(string $filePath, ?string $showName = null): bool
    {
        if (file_exists($filePath)) {
            $showName = ($showName) ?: basename($filePath) . PHP_EOL;
            $this->mail->addAttachment($filePath, $showName);

            return true;
        }
        $this->error = "Attachment file path {$filePath} not exist";

        return false;
    }

    public function send(): bool
    {
        if($this->readyToSend) {
            if($this->mail->Send()) {
                return true;
            }
            else
            {
                $this->error = $this->mail->ErrorInfo;
            }
        }
        return false;
    }

}
