<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gemvc\Core;

use Gemvc\Helper\TypeHelper;
use PHPMailer\PHPMailer\PHPMailer;

class GemvcMail
{
    public ?string $error;
    protected PHPMailer $phpMailer;

    public function __construct(string $subject, string $fromEmail, string $language = null)
    {
        $this->error = null;
        $language = ($language) ?: DEFAULT_LANGUAGE;
        $mailConfiguration = EMAILS[$fromEmail];

        $this->phpMailer = new PHPMailer(true);
        $this->phpMailer->isHTML(true);
        //set to 3 in production 3 in develop
        $this->phpMailer->SMTPDebug = 0;
        $this->phpMailer->setLanguage($language);
        $this->phpMailer->Host = $mailConfiguration['host'];
        $this->phpMailer->SMTPAuth = true;
        $this->phpMailer->Username = $fromEmail;
        $this->phpMailer->Password = $mailConfiguration['password'];
        $this->phpMailer->SMTPSecure = $mailConfiguration['connection_type'];
        $this->phpMailer->From = $fromEmail;
        $this->phpMailer->FromName = $mailConfiguration['from_name'];
        $this->phpMailer->Subject = $subject;
        $this->phpMailer->Port = intval( $mailConfiguration['port_number']);
    }

    public function addReciver(string $email, string $reciverName = null): bool
    {
        $reciverName = ($reciverName) ?: $email;
        if (TypeHelper::safeEmail($email)) {
            $this->phpMailer->addAddress($email, $reciverName);

            return true;
        }
        $this->error = "Given Email address: {$email} is wrong formatted";

        return false;
    }

    public function addCC(string $email, string $reciverName = null): bool
    {
        $reciverName = ($reciverName) ?: $email;
        if (TypeHelper::safeEmail($email)) {
            $this->phpMailer->addCC($email, $reciverName);

            return true;
        }
        $this->error = "Given CC Email address: {$email} is wrong formatted";

        return false;
    }

    public function addBCC(string $email, string $reciverName = null): bool
    {
        $reciverName = ($reciverName) ?: $email;
        if (TypeHelper::safeEmail($email)) {
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

}
