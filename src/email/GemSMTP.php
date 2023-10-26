<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 * Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace GemLibrary\Email;

use Gemvc\Helper\WebHelper;
use PHPMailer\PHPMailer\PHPMailer;

class GemSMTP
{
    public ?string $error;
    private bool $readyToSend = false;
    protected PHPMailer $phpMailer;

    /**
     * Constructor
     * @param int $smtpDebugLevel
     * @param string $language 
     * this class is wrapper from PHPMailer
     * smtp debug level between 0 to 4 and it is tls enabled
     */
    public function __construct(string $host, string $username, string $password, string $port, ?int $smtpDebugLevel = 3)
    {
        $this->error = 'before send you shall call function createEmail()';
        $this->phpMailer = new PHPMailer(true);
        $this->phpMailer->SMTPAuth = true;
        $this->phpMailer->isSMTP();
        $this->phpMailer->isHTML(true);
        $this->phpMailer->Host = $host;
        $this->phpMailer->Username = $username;
        $this->phpMailer->Password = $password;
        $this->phpMailer->Port = $port;
        $this->phpMailer->SMTPDebug = $smtpDebugLevel;
        $this->phpMailer->SMTPSecure = 'tls';


    }

    /**
     * @param string $senderEmail
     * @param string $senderName
     * @param string $reciverEmail
     * @param string $reciverName
     * @param string $subject
     * @param string $emailContent
     * @param string $contentLanguage
     * @return bool
     * Tipp: for add Attachment you can use $this->addAttachment();
     * Tipp: you can use method $content  = $this->phpMailer->$mail->msgHTML(file_get_contents('contents.html'), __DIR__);
     * and use result in this function $emailContent in case of wish to use  HTML Template file
     */
    public function createMail(string $senderEmail, string $senderName, string $reciverEmail, string $reciverName, string $subject, string $emailContent, string $contentLanguage): bool
    {
        if ($this->phpMailer->setLanguage($contentLanguage)) {
            if (WebHelper::isValidEmail($senderEmail)) {
                if ($this->phpMailer->setFrom($senderEmail, $senderName)) {
                    if (WebHelper::isValidEmail($reciverEmail)) {
                        if ($this->addReciver($reciverEmail, $reciverName)) {
                            $this->phpMailer->Subject = $subject;
                            $this->phpMailer->Body = $emailContent;
                            $this->readyToSend = true;
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
        if (WebHelper::isValidEmail($email)) {
            $this->phpMailer->addAddress($email, $reciverName);

            return true;
        }
        $this->error = "Given Email address: {$email} is wrong formatted";

        return false;
    }

    public function addCC(string $email, string $reciverName = null): bool
    {
        $reciverName = ($reciverName) ?: $email;
        if (WebHelper::isValidEmail($email)) {
            $this->phpMailer->addCC($email, $reciverName);

            return true;
        }
        $this->error = "Given CC Email address: {$email} is wrong formatted";

        return false;
    }

    public function addBCC(string $email, string $reciverName = null): bool
    {
        $reciverName = ($reciverName) ?: $email;
        if (WebHelper::isValidEmail($email)) {
            $this->phpMailer->addBCC($email, $reciverName);

            return true;
        }
        $this->error = "Given BCC Email address: {$email} is wrong formatted";

        return false;
    }

    public function addAttachment(string $filePath, ?string $showName = null): bool
    {
        if (file_exists($filePath)) {
            $showName = ($showName) ?: basename($filePath) . PHP_EOL;
            $this->phpMailer->addAttachment($filePath, $showName);

            return true;
        }
        $this->error = "Attachment file path {$filePath} not exist";

        return false;
    }

    public function send(): bool
    {
        if($this->readyToSend)
        {
            if($this->phpMailer->Send())
            {
                return true;
            }
            else
            {
                $this->error = $this->phpMailer->ErrorInfo;
            }
        }
        return false;
    }

}