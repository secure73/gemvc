<?php
namespace GemLibrary\Http;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * @public function setToken(string $token):void
 * @function create(int $userId, int $timeToLiveSecond): string
 * @function verify(): bool
 * @function renew(int $extensionTime_sec): false|string
 * @function GetType():string|null
 */
class GemToken
{
    public int       $exp;
    public bool      $isTokenValid;
    public int       $user_id;
    public string    $type;//access or refresh
    public array     $payload;/** @phpstan-ignore-line */
    public ?string   $token_id;
    public ?string   $iss;
    public ?string   $role;
    public ?int      $company_id;
    public ?int      $employee_id;
    public ?string   $error;
    private string   $_secret;
    private ?string  $_token;  

    public function __construct(string $secret , string $issuer = null)
    {
        $this->_token = null;
        $this->error = null;
        $this->iss = null;
        $this->type = 'not defined';
        $this->user_id = 0;
        if($issuer)
        {
            $this->iss = $issuer;
        }
        $this->exp = 0;
        $this->isTokenValid = false;
        $this->payload = [];
        $this->_secret = $secret;
    }

    /**
     * @param string $token
     * @return void
     */
    public function setToken(string $token):void
    {
        $this->_token = $token;
    }


    /**
     * @param int $userId
     * @param int $timeToLiveSecond
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
        if(isset($this->company_id))
        {
            $payloadArray['company_id'] = $this->company_id;
        }
        if(isset($this->employee_id))
        {
            $payloadArray['employee_id'] = $this->employee_id;
        }
        return JWT::encode($payloadArray, $this->_secret, 'HS256');
    }

    /**
     * @return false|GemToken
     * @description pure token without Bearer you can use WebHelper::BearerTokenPurify() got get pure token
     */
    public function verify(string $jwt_token): false|GemToken
    {
        try {
            $decodedToken = JWT::decode($jwt_token, new Key($this->_secret, 'HS256'));
            if (isset($decodedToken->user_id) && $decodedToken->exp > time() && $decodedToken->user_id>0) {
                $this->token_id = $decodedToken->token_id;
                $this->user_id = (int)$decodedToken->user_id;
                $this->exp = $decodedToken->exp;
                $this->iss = $decodedToken->iss;
                $this->payload = $decodedToken->payload;
                $this->isTokenValid = true;
                $this->type = $decodedToken->type;
                $this->role = $decodedToken->role;
                if(isset($decodedToken->company_id))
                {
                    $this->company_id = $decodedToken->company_id;
                }
                if(isset($decodedToken->employee_id))
                {
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
     * @param int $extensionTime_sec
     * @return false|string
     */
    public function renew(string $token , int $extensionTime_sec): false|string
    {
        if ($this->verify($token)) {
            return $this->create($this->user_id, $extensionTime_sec);
        }
        return false;
    }

    /**
     * @return string|null
     * @description Returns type without validation token
     */
    public function GetType(string $token = null):string|null
    {
        if($token)
        {
            $this->_token = $token;
        }

        if(!$this->_token)
        {
            $this->error = 'please set token first directly in function GetType or with setToken(string $token)';
            return null;
        }
        
        $tokenParts = $this->isJWT($this->_token);
        if(!$tokenParts)
        {
            return null;
        }

        // The payload is the second part of the token
        $payloadBase64 = $tokenParts[1];

        // Decode the payload from base64
        $payload = json_decode(base64_decode($payloadBase64), true);

        // Access the "type" property from the payload
        if (isset($payload['type'])) /** @phpstan-ignore-line */
        {
            return $payload['type'];/** @phpstan-ignore-line */
        } 
        else return null;
    }

    /**
     * @param string $string
     * @return false|array<string>
     * @success: return array of each parts for future use if needed
     */
    public static function isJWT(string $string):false|array
    {
        $tokenParts = explode('.',$string);
        if(!$tokenParts || count($tokenParts) !== 3)/**@phpstan-ignore-line */
        {
            return false;
        }
        foreach($tokenParts as $part)
        {
            if(!strlen($part))
            {
                return false;
            }
        }
        // The payload is the second part of the token
       return $tokenParts;
    }
}
