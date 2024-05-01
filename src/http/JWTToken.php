<?php
namespace GemLibrary\Http;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * @public   function setToken(string $token):void
 * @function create(int $userId, int $timeToLiveSecond): string
 * @function verify(): bool
 * @function renew(int $extensionTime_sec): false|string
 * @function GetType():string|null
 */
class JWTToken
{
    public int       $exp;
    public bool      $isTokenValid;
    public int       $user_id;
    public string    $type;//access or refresh
    private string   $_secret;
    /**
     * @var array<mixed> $payload
     */
    public array     $payload;
    public ?string   $token_id;
    public ?string   $iss;
    public ?string   $role;
    public ?int      $company_id;
    public ?int      $employee_id;
    public ?string   $error;
    private ?string  $_token;  

    public function __construct()
    {
        $this->_token = null;
        $this->error = null;
        $this->iss = null;
        $this->type = 'not defined';
        $this->user_id = 0;
        $this->exp = 0;
        $this->isTokenValid = false;
        $this->payload = [];
    }

    /**
     * @param  string $token
     * @return void
     */
    public function setToken(string $token):void
    {
        $this->_token = $token;
    }

    public function setSecret(string $secret):void
    {
        $this->_secret = $secret;
    }


    /**
     * @param  int $userId
     * @param  int $timeToLiveSecond
     * @return string
     */
    public function create(int $userId, int $timeToLiveSecond): string
    {
        $payloadArray = [
            'token_id' => microtime(true),
            'user_id' => $userId,
            'iss' => $this->iss,
            'exp' => (time() + $timeToLiveSecond),
            'type' => $this->type,
            'payload' => $this->payload,
            'role' => $this->role
        ];
        if(isset($this->company_id)) {
            $payloadArray['company_id'] = $this->company_id;
        }
        if(isset($this->employee_id)) {
            $payloadArray['employee_id'] = $this->employee_id;
        }
        return JWT::encode($payloadArray, $this->_secret, 'HS256');
    }

    /**
     * @return      false|JWTToken
     * @description pure token without Bearer you can use WebHelper::BearerTokenPurify() got get pure token
     */
    public function verify(): false|JWTToken
    {
        try {
            $decodedToken = JWT::decode($this->_token, new Key($this->_secret, 'HS256'));
            if (isset($decodedToken->user_id) && $decodedToken->exp > time() && $decodedToken->user_id>0) {
                $this->token_id = $decodedToken->token_id;
                $this->user_id = (int)$decodedToken->user_id;
                $this->exp = $decodedToken->exp;
                $this->iss = $decodedToken->iss;
                $this->payload = $decodedToken->payload;
                $this->isTokenValid = true;
                $this->type = $decodedToken->type;
                $this->role = $decodedToken->role;
                if(isset($decodedToken->company_id)) {
                    $this->company_id = $decodedToken->company_id;
                }
                if(isset($decodedToken->employee_id)) {
                    $this->employee_id = $decodedToken->employee_id;
                }
                $this->error = null;
                return $this;
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
        return false;
    }

    /**
     * @param  int $extensionTime_sec
     * @return false|string
     */
    public function renew(int $extensionTime_sec): false|string
    {
        if ($this->verify($this->_token)) {
            return $this->create($this->user_id, $extensionTime_sec);
        }
        return false;
    }

    /**
     * @return      string|null
     * @description Returns type without validation token
     */
    public function GetType(string $token = null):string|null
    {
        if($token) {
            $this->_token = $token;
        }

        if(!$this->_token) {
            $this->error = 'please set token first directly in function GetType or with setToken(string $token)';
            return null;
        }
        if(!$this->isJWT($this->_token)) {
            return null;
        }
        $tokenParts = explode('.', $this->_token);
        $payloadBase64 = $tokenParts[1];
        $payload = json_decode(base64_decode($payloadBase64), true);
        /**@phpstan-ignore-next-line */
        if (isset($payload['type'])) 
        {
            /**@phpstan-ignore-next-line */
            return $payload['type'];
        } 
        else { return null;
        }
    }

    /**
     * @param  string $string
     * @return bool
     */
    public static function isJWT(string $string):bool
    {
        $tokenParts = explode('.', $string);
        if (count($tokenParts) !== 3) {
            return false;
        }
        return true;
    }
}