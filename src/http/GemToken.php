<?php
namespace GemLibrary\Http;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GemLibrary\Helper\TypeHelper;

class GemToken
{
    public string   $token_id;
    public ?string   $iss;
    public int      $exp;
    public bool     $isTokenValid;
    public string   $type;
    public array    $payload;/** @phpstan-ignore-line */
    public int      $user_id;
    public ?string  $error;
    public ?string  $userMachine;
    public ?string  $ip;
    private string  $_secret;

    public function __construct(string $secret , string $issuer = null)
    {
        $this->token_id = 'Not Initialized';
        $this->user_id = 0;
        if($issuer)
        {
            $this->iss = $issuer;
        }
        $this->exp = 0;
        $this->isTokenValid = false;
        $this->error = 'Not Initialized';
        $this->payload = [];
        $this->type = 'not defined';
        $this->_secret = $secret;
    }


    /**
     * @param string $secret
     * @param int $userId
     * @param int $timeToLiveSecond
     * @param array<mixed> $payload
     * @param string $type
     * @param null|string $issuer
     * @param null|string $ipAddressTobeSensitive
     * @param null|string $userMachinToBeSensetive 
     * @return string
     */
    public function create(string $type , int $userId, int $timeToLiveSecond, array $payload, string $ipAddressTobeSensitive = null, string $userMachinToBeSensetive = null): string
    {
        $payloadArray = [
            'token_id' => TypeHelper::guid(),
            'user_id' => $userId,
            'iss' => $this->iss,
            'exp' => (time() + $timeToLiveSecond),
            'type' => $type,
            'payload' => $payload
        ];
        return JWT::encode($payloadArray, $this->_generate_key($ipAddressTobeSensitive, $userMachinToBeSensetive), 'HS256');
    }

    /**
     * @param string $token
     * @description pure token without Bearer you can use WebHelper::BearerTokenPurify() got get pure token
     */
    public function validate(string $token,string $ip = null, string $userMachine = null): bool
    {
        try {
            $decodedToken = JWT::decode($token, new Key($this->_generate_key($ip, $userMachine), 'HS256'));
            if (isset($decodedToken->user_id) && $decodedToken->exp > time() && $decodedToken->user_id>0) {
                $this->token_id = $decodedToken->token_id;
                $this->user_id = (int)$decodedToken->user_id;
                $this->exp = $decodedToken->exp;
                $this->iss = $decodedToken->iss;
                $this->payload = $decodedToken->payload;
                $this->isTokenValid = true;
                $this->ip = $ip;
                $this->userMachine = $userMachine;
                $this->type = $decodedToken->type;
                $this->error = null;
                return true;
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
        return false;
    }

    public function renew(string $token, int $extensionTime_sec): false|string
    {
        if ($this->validate($token, $this->ip, $this->userMachine)) {
            return $this->create($this->type , $this->user_id, $extensionTime_sec, $this->payload ,$this->ip, $this->userMachine);
        }
        return false;
    }

    /**
     * @param string $token
     * @return string|null
     * @description Returns type without validation token
     */
    public function GetType(string $token):string|null
    {
        $tokenParts = explode('.', $token);

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
     * @param string $ip
     * @param string $machin
     * @return string
     */
    private function _generate_key(string $ip = null, string $machin = null): string
    {
        return $this->_secret . $ip . $machin;
    }
}
