<?php
namespace GemLibrary\Http;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GemLibrary\Helper\TypeHelper;

class GemToken
{
    public string   $tokenId;
    public string   $iss;
    public int      $exp;
    public bool     $isTokenValid;
    public string   $type;
    public array    $payload;/** @phpstan-ignore-line */
    public int      $user_id;
    public ?string  $error;
    public ?string  $userMachine;
    public ?string  $ip;

    public function __construct()
    {
        $this->tokenId = 'Not Initialized';
        $this->user_id = 0;
        $this->iss = '';
        $this->exp = 0;
        $this->isTokenValid = false;
        $this->error = 'Not Initialized';
        $this->payload = [];
        $this->type = 'not defined';
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
    public function create(string $type ,string $secret, int $userId, int $timeToLiveSecond, array $payload, string $issuer = null, string $ipAddressTobeSensitive = null, string $userMachinToBeSensetive = null): string
    {
        $payloadArray = [
            'tokenId' => TypeHelper::guid(),
            'userId' => $userId,
            'iss' => $issuer,
            'exp' => (time() + $timeToLiveSecond),
            'type' => $type,
            'payload' => $payload
        ];
        return JWT::encode($payloadArray, self::_generate_key($secret, $ipAddressTobeSensitive, $userMachinToBeSensetive), 'HS256');
    }

    /**
     * @param string $token
     * @description pure token without Bearer you can use WebHelper::BearerTokenPurify() got get pure token
     */
    public function validate(string $token, string $secret, string $ip = null, string $userMachine = null): bool
    {
        try {
            $decodedToken = JWT::decode($token, new Key(self::_generate_key($secret, $ip, $userMachine), 'HS256'));
            if (isset($decodedToken->userId)) {
                $this->tokenId = $decodedToken->tokenId;
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

    public function renew(string $token, string $secret, int $extensionTime_sec): false|string
    {
        if ($this->validate($token, $secret, $this->ip, $this->userMachine)) {
            return $this->create($this->type ,$secret, $this->user_id, $extensionTime_sec, $this->payload, $this->iss ,$this->ip, $this->userMachine);
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
     * @param string $secret
     * @param string $ip
     * @param string $machin
     * @return string
     */
    private static function _generate_key(string $secret, string $ip = null, string $machin = null): string
    {
        $miniSecret = 'it is mini secret that use to add to md5!';
        if ($ip) {
            $ip = md5($ip . $miniSecret);
        }
        if ($machin) {
            $machin = md5($machin . $miniSecret);
        }
        return $secret . $ip . $machin;
    }
}
