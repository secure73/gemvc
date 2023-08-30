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
    /** @phpstan-ignore-next-line */
    public array    $permissions;
    public ?string  $token = null;
    public ?string  $type;
    public ?string  $projectName;
    public ?int     $projectId;
    public ?int     $permissionId;
    public ?int     $jobId;
    public ?int     $instituteId;
    public ?string  $error;

    private ?RequestDispatcher $request;

    public function __construct(?RequestDispatcher $request = null)
    {
        $this->tokenId = 'Not Initialized';
        $this->permissions = [];
        $this->error = 'Not Initialized';
        $this->isTokenValid = false;
        $this->exp = 0;
        $this->iss = '';
        $this->request = $request;
        $this->userId = null;
        $this->request = $request;
        if ($this->request) {
            $this->token = $this->_getBearerToken($request->token);
            if (!$this->token) {
                $this->error = 'Bearer Token is not found in request Dispatcher';
            }
            else{
                $this->validate();
            }
        }
    }


    /**
     * @param string $type
     *
     * @return string
     * create JWT TOKEN
     */
    public function create(int|string $userId, string $type, int $valid_till_sec): string
    {
        $this->tokenId = TypeHelper::guid();
        $instituteId = $this->instituteId ?? null;
        $projectId = $this->projectId ?? null;
        $projectName = $this->projectName ?? null;
        $permissionId = $this->permissionId ?? null;
        $jobId = $this->jobId ?? null;
        $payloadArray = [
            'userId' => $userId, 'type' => $type, 'iss' => URL, 'exp' => (time() + $valid_till_sec), 'projectName' => $projectName, 'projectId' => $projectId, 'permissionId' => $permissionId,
            'jobId' => $jobId, 'instituteId' => $instituteId, 'permissions' => $this->permissions,
        ];
        return JWT::encode($payloadArray, $this->_generate_key(), 'HS256');
    }

    public function validate(): bool
    {
        if ($this->token) {
            try {
                $decodedToken = JWT::decode($this->token, new Key($this->_generate_key(), 'HS256'));
                if (isset($decodedToken->userId)) {
                    $this->tokenId = $decodedToken->tokenId ?? 'not defined';
                    $this->type = $decodedToken->type ?? null;
                    $this->userId = $decodedToken->userId;
                    $this->exp = $decodedToken->exp ?? 0;
                    $this->iss = $decodedToken->iss ?? '';

                    $permissions = $decodedToken->permissions ?? [];
                    $array = [];
                    foreach ($permissions as $key => $val) {
                        $array[$key] = $val;
                    }
                    $this->permissions = $array;
                    $this->projectName = $decodedToken->projectName ?? '';
                    $permissionId = $decodedToken->permissionId ?? null;
                    $this->permissionId = $permissionId ? (int) $permissionId : null;

                    $jobId = $decodedToken->jobId ?? null;
                    $this->jobId = $jobId ? (int) $jobId : null;

                    $projectId = $decodedToken->projectId ?? null;
                    $this->projectId = $projectId ? (int) $projectId : null;

                    $this->isTokenValid = true;
                    $this->error = null;
                    return true;
                }
            } catch (\Exception $e) {
                $this->error = $e->getMessage();
            }
        }
        return false;
    }

    public function renew(int $extensionTime_sec): false|string
    {
        if ($this->isTokenValid) {
            return $this->create($this->userId, $this->type, $extensionTime_sec);
        }
        return false;
    }

    private function _getBearerToken(string $BearerToken): null|string
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
            $ipRestrict = md5($this->request->remoteAddress);
        }
        if (TOKEN_USER_MACHINE_RESTRICT) {
            $machinRestrict = md5($this->request->userMachine);
        }
        return API_TOKEN_SECRET_KEY . $ipRestrict . $machinRestrict;
    }
}
