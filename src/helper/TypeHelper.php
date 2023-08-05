<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gemvc\Helper;

class TypeHelper
{
    public static function justInt(mixed $var): false|int
    {
        if (\is_int($var)) {
            return $var;
        }

        return false;
    }

    public static function justIntPositive(mixed $var): false|int
    {
        if (\is_int($var) && $var > 0) {
            return $var;
        }

        return false;
    }

    /**
     * @param string $url_string
     *                           check if given string is valid URL
     *
     * @retrun given string in case of valid and null if string is not valid url format.
     */
    public static function safeURL(string $url_string): null|string
    {
        if (filter_var($url_string, FILTER_VALIDATE_URL)) {
            return $url_string;
        }

        return null;
    }

    /**
     * @return string GUID
     *                create GUID
     */
    public static function guid(): string
    {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = \chr(\ord($data[6]) & 0x0F | 0x40); // set version to 0100
        $data[8] = \chr(\ord($data[8]) & 0x3F | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s%s%s%s%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * if string is valid email format return string else return null.
     */
    public static function safeEmail(string $emailString): null|string
    {
        $safe = null;
        $emailString = strtolower(trim($emailString));
        if (filter_var($emailString, FILTER_VALIDATE_EMAIL)) {
            $safe = $emailString;
        }

        return $safe;
    }

    /**
     * create string Unix Y-m-d H:i:s time now.
     */
    public static function timeStamp(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * create random string.
     */
    public static function randomString(int $stringLength): string
    {
        $result = '';
        $characters = ['2', '_', '3', '4', '5', '&', '6', '7', '8', '9', '!', '$', '%', '&', '(', ')', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'K', 'M', 'N', 'P', 'Q', 'R', 'S', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'k', 'm', 'n', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
        // 59 character array
        for ($i = 0; $i < $stringLength; ++$i) {
            $int = random_int(0, 58);
            $result = $result . $characters[$int];
        }

        return $result;
    }

    /**
     * @return false|string
     *                      encrypt and dycrypt string
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

    /**
     * @param object|stdClass $object
     * @return array<string>
     * id is not in the non null!
     */
    public static function getNonNullableProperties(object $object):array {
        $reflection = new \ReflectionClass($object);
        $properties = $reflection->getProperties();
        $nonNullableProperties = [];
    
        foreach ($properties as $property) {
            $propertyName = $property->getName();
    
            // Get the property type
            $propertyType = $property->getType();
    
            // Check if the property has a type declaration and is not nullable but not id!
            if ($propertyName !=='id' && $propertyType !== null && !$propertyType->allowsNull()) {
                $nonNullableProperties[] = $propertyName;
            }
        }
    
        return $nonNullableProperties;
    }
}

    
    
    
    
    
    
    
