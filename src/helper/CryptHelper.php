<?php
namespace Gemvc\Helper;

class CryptoHelper {

    public static function hashPassword(string $password):string
    {
        return password_hash(trim($password), PASSWORD_ARGON2I);
    }

    public static function passwordVerify(string $passwordToCheck, string $hash): bool
    {
        return password_verify(trim($passwordToCheck), $hash);
    }

    /**
     * @param string $string
     * @param string $secret
     * @param string $iv
     * @param string $action
     * @return false|string
     * action = d decrypt , default action = encrypt
     */
    public static function crypt(string $string, string $secret, string $iv, string $action = 'e'): false|string
    {
        $output = false;
        $key = hash(SHA_ALGORYTHEM, $secret);
        $iv = substr(hash(SHA_ALGORYTHEM, $iv), 0, 16);
        if ('e' === $action) {
            $encrypted = openssl_encrypt($string, ENCRYPTION_ALGORYTHEM, $key, 0, $iv);
            if ($encrypted) {
                $output = base64_encode($encrypted);
            }
        } elseif ('d' === $action) {
            $bse64Decode = base64_decode($string, true);
            if (\is_string($bse64Decode) && '' !== $bse64Decode) {
                $output = openssl_decrypt($bse64Decode, ENCRYPTION_ALGORYTHEM, $key, 0, $iv);
            }
        }
        return $output;
    }

    public static function encryptString(string $string, string $key):false|string
    {
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        if($ivLength)
        {
            $iv = openssl_random_pseudo_bytes($ivLength);
            $encrypted = openssl_encrypt($string, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
            if($encrypted)
            {
                return base64_encode($iv . hash_hmac('sha256', $encrypted, $key, true) . $encrypted);
            }
        }
        return false;
    }

    public static function decryptString(string $encryptedString, string $key):false|string
    {
        $data = base64_decode($encryptedString);
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        if($ivLength)
        {
            $iv = substr($data, 0, $ivLength);
            $hmac = substr($data, $ivLength, 32);
            $encrypted = substr($data, $ivLength + 32);
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
            $calculatedHmac = hash_hmac('sha256', $encrypted, $key, true);
            if (hash_equals($hmac, $calculatedHmac)) {
                return $decrypted;
            } else {
                return false;
            }
        }
        return false;
    }

}