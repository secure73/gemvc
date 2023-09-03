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

use Gemvc\Core\RequestDispatcher;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Gemvc\Helper\TypeHelper;

class GemToken
{
    public string   $tokenId;
    public ?int     $userId;
    public string   $iss;
    public int      $exp;
    public bool     $isTokenValid;
    public ?string  $token = null;
    public ?string  $error;
    public array    $payload;
    public ?string $remoteIP;
    public ?string $remoteMachine;

    public function __construct(string $remoteIP , string $remoteMachine)
    {
        $this->tokenId = 'Not Initialized';
        $this->userId = null;
        $this->iss = '';
        $this->exp = 0;
        $this->isTokenValid = false;
        $this->error = 'Not Initialized';
        $this->payload = [];
        $this->remoteIP = $remoteIP;
        $this->remoteMachine = $remoteMachine;
    }


    /**
     * @param string $type
     *
     * @return string
     * create JWT TOKEN
     */
    public function create(int|string $userId, int $valid_till_sec ,array $payload ): string
    {
        $payloadArray = [
             'tokenId'=> TypeHelper::guid(), 'userId' => $userId,'iss' => URL, 'exp' => (time() + $valid_till_sec), 
             'payload' => $payload
        ];
        return JWT::encode($payloadArray, $this->_generate_key(), 'HS256');
    }

    public function validate(string $token): bool
    {
            try {
                $decodedToken = JWT::decode($token, new Key($this->_generate_key(), 'HS256'));
                if (isset($decodedToken->userId)) {
                    $this->tokenId = $decodedToken->tokenId;
                    $this->userId = $decodedToken->userId;
                    $this->exp = $decodedToken->exp;
                    $this->iss = $decodedToken->iss;
                    $this->payload = $decodedToken->payload;
                    $this->isTokenValid = true;
                    $this->error = null;
                    return true;
                }
            } catch (\Exception $e) {
                $this->error = $e->getMessage();
            }
        return false;
    }

    public function renew(int $extensionTime_sec): false|string
    {
        if ($this->isTokenValid) {
            return $this->create($this->userId, $extensionTime_sec , $this->payload);
        }
        return false;
    }

    public function _getBearerToken(string $BearerToken): null|string
    {

        if (preg_match('/Bearer\s(\S+)/', $BearerToken, $matches)) {
            $BearerToken = $matches[1];
            return $BearerToken;
        }
        return null;
    }

    private function _generate_key(): string
    {
        $ipRestrict = '';
        $machinRestrict = '';
        // @phpstan-ignore-next-line
        if (TOKEN_IP_RESTRICT) {
            $ipRestrict = md5($this->remoteIP);
        }
        if (TOKEN_USER_MACHINE_RESTRICT) {
            $machinRestrict = md5($this->remoteMachine);
        }
        return API_TOKEN_SECRET_KEY . $ipRestrict . $machinRestrict;
    }
}
