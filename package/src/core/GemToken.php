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

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Gemvc\Helper\TypeHelper;

class GemToken
{
    public string $id;
    public string $iss;
    public int $exp;
    public bool $token_valid;
    public ?int $user_id = null;
    /** @phpstan-ignore-next-line */
    public array $permissions;
    public ?string $token_error;
    public string $user_machine;
    public string $ip;
    public string $token;
    public ?string $type;
    public ?string $project_name;
    
    public ?int $project_id;
    public ?int $ppp_id;
    public ?int $job_id;
    public ?int $institute_id;
    public ?string $headers;

    public function __construct()
    {
        $this->id = 'not Initialized';
        $this->permissions = [];
        $this->user_id = null;
        $this->token_valid = false;
        $this->token_error = 'not Initialized';
        $this->exp = 0;
        $this->iss = '';
        $this->user_machine = trim($_SERVER['HTTP_USER_AGENT']);
        $this->ip = $this::getIp();
        $this->_getAuthorizationHeader();
        $this->_getBearerToken();
        $this->validate();

    }

    /**
     * @param string $type
     *
     * @return string
     *                create JWT TOKEN
     */
    public function create(int|string $user_id, ?string $type, int $valid_till_sec): string
    {
        $this->id = TypeHelper::guid();
        $institute_id = $this->institute_id ?? null;
        $project_id = $this->project_id ?? null;
        $project_name = $this->project_name ?? null;
        $ppp_id = $this->ppp_id ?? null;
        $job_id = $this->job_id ?? null;
        $payloadArray = [
            'user_id' => $user_id, 'type' => $type, 'iss' => URL, 'exp' => (time() + $valid_till_sec), 'project_name' => $project_name, 'project_id' => $project_id, 'ppp_id' => $ppp_id,
            'job_id' => $job_id, 'institute_id' => $institute_id, 'permissions' => $this->permissions,
        ];

        return JWT::encode($payloadArray, $this->_generate_key(), 'HS256');
    }

    public function validate(): bool
    {
        $this->token_valid = false;
        $result = false;
        if (isset($this->token)) {
            try {
                $decodedToken = JWT::decode($this->token, new Key($this->_generate_key(), 'HS256'));
                if (isset($decodedToken->user_id)) {
                    $this->id = $decodedToken->type ?? 'not defined';
                    $this->type = $decodedToken->type ?? null;
                    $this->user_id = $decodedToken->user_id;
                    $this->exp = $decodedToken->exp ?? 0;
                    $this->iss = $decodedToken->iss ?? '';

                    $permissions = $decodedToken->permissions ?? [];
                    $array = [];
                    foreach ($permissions as $key => $val) {
                        $array[$key] = $val;
                    }

                    $this->permissions = $array;
                    $this->project_name = $decodedToken->project_name ?? '';

                    $ppp_id = $decodedToken->ppp_id ?? null;
                    $this->ppp_id = $ppp_id ? (int) $ppp_id : null;

                    $job_id = $decodedToken->job_id ?? null;
                    $this->job_id = $job_id ? (int) $job_id : null;

                    $project_id = $decodedToken->project_id ?? null;
                    $this->project_id = $project_id ? (int) $project_id : null;

                    $this->token_valid = true;
                    $this->token_error = '';
                    $result = true;
                }
            } catch (\Exception $e) {
                $this->token_error = $e->getMessage();
            }
        }

        return $result;
    }

    public function renew(string $token, int $extensionTime_sec): false|string
    {
        $result = false;
        $this->token = $token;
        if ($this->validate()) {
            if ($this->user_id) {
                $result = $this->create($this->user_id, $this->type, $extensionTime_sec);
            }
        }

        return $result;
    }

    public static function getIp(): string
    {
        return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_FORWARDED'] ?? $_SERVER['HTTP_FORWARDED_FOR'] ?? $_SERVER['HTTP_FORWARDED'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }

    // Private Functions
    private function _getAuthorizationHeader(): void
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx or fast CGI
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (\function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            // print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        $this->headers = $headers;
    }

    private function _getBearerToken(): void
    {
        if (!empty($this->headers)) {
            if (preg_match('/Bearer\s(\S+)/', $this->headers, $matches)) {
                $this->token = $matches[1];
            }
        }
    }

    private function _generate_key(): string
    {
        $key_result = API_TOKEN_SECRET_KEY;
        $user_ip = $this->ip;
        // @phpstan-ignore-next-line
        if (TOKEN_IP_RESTRICT || TOKEN_USER_MACHINE_RESTRICT) {
            $user_ip = $this->ip;
            /** @phpstan-ignore-next-line */
            if (isset($this->project_name) && is_array($this->project_name)) {
                /** @phpstan-ignore-next-line */
                $user_ip = (\array_key_exists($this->project_name, PROJECT_SERVER_IPS)) ? PROJECT_SERVER_IPS[$this->project_name] : $this->ip;
            }
            // @phpstan-ignore-next-line
            if (TOKEN_IP_RESTRICT && TOKEN_USER_MACHINE_RESTRICT) {
                $key_result .= md5($user_ip.$this->user_machine);
            } else {
                // @phpstan-ignore-next-line
                TOKEN_IP_RESTRICT ? $key_result .= md5($user_ip) : $key_result .= md5($this->user_machine);
            }
        }

        return $key_result;
    }
}
