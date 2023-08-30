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

use Gemvc\Core\GemToken;
use Gemvc\Core\RequestDispatcher;
class Security
{
    public ?int     $error_code;
    public ?string  $error_message;
    public RequestDispatcher $request;
    public GemToken $gemToken;

    public function __construct(RequestDispatcher $request)
    {
        $this->request = $request;
    }

    public function check(): bool
    {
        if ($this->isRequestOnPublicService()) {
            return true;
        } else {
            if ($this->token_valid) {
                if (in_array(
                    $this->functionName,
                    $this->permissions[$this->serviceName],
                    true
                )) {
                    return true;
                } else {
                    $this->error_code = 401;
                    $this->error_message = 'you dont have permission for this action';

                }
            } else {
                $this->error_code = 403;
                $this->error_message = 'Token: ' . $this->token_error;
            }
        }
        return false;
    }


    public static function safeInput(string $string): string
    {
        $string = trim($string);
        return htmlentities($string, ENT_QUOTES);
    }

    public static function htmlDecode(string $html_encoded): string
    {
        return html_entity_decode($html_encoded, ENT_QUOTES);
    }

    /**
     * @return bool|\Exception
     *                         this method check if POST and GET request are safe to use
     */
    public static function safePost(): bool|\Exception
    {
        $result = false;

        try {
            foreach ($_GET as $key => $value) {
                $val = self::safeInput($value);
                $_GET[$key] = $val;
            }
            foreach ($_POST as $key => $val) {
                $val = self::safeInput($val);
                $_POST[$key] = $val;
            }

            return true;
        } catch (\Exception $e) {
            return $e;
        }
    }

    public static function hashPassword(string $passwordToHash): string
    {
        return password_hash(trim($passwordToHash), PASSWORD_ARGON2I);
    }

    public static function passwordVerify(string $passwordToCheck, string $hash): bool
    {
        return password_verify(trim($passwordToCheck), $hash);
    }

    private function isRequestOnPublicService(): bool
    {
        $result = false;
        if ($this->request->service) {
            if (isset(PUBLIC_SERVICES[$this->request->service])) {
                foreach (PUBLIC_SERVICES[$this->serviceName] as $item) {

                    if ($item == $this->functionName) {
                        $result = true;
                    }
                }
            }
        }
        return $result;
    }
}
