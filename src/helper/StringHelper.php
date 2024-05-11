<?php

namespace GemLibrary\Helper;

class StringHelper
{
    public static function capitalizeAfterSpace(string $string): string 
    {
        $words = explode(" ", $string);
        $result = array();
    
        foreach ($words as $word) {
            $result[] = ucfirst($word);
        }
    
        return implode(" ", $result);
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

    public static function sanitizedString(string $incoming_string):string|null
    {
        $pattern = '/^[a-zA-Z0-9_\-\/\(\);,.,äÄöÖüÜß  ]{1,255}$/';
        // Check if the User-Agent matches the pattern.
        if (preg_match($pattern, $incoming_string)) {
            // The User-Agent is safe.
            return $incoming_string;
        } else {
            // The User-Agent is not in the expected format; handle it accordingly.
            return null;
        }
    }

    
    /**
     * @param  string $url
     * @return bool
     * if given string is has valid url format return true
     */
    public static function isValidUrl(string $url):bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * @param  string $email
     * @return bool
     * if given string is has valid email format return true
     */
    public static function isValidEmail(string $email)
    {
        // Use filter_var with FILTER_VALIDATE_EMAIL
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * @param string $url_string
     * check if given string is valid URL
     *
     * @return given string in case of valid and null if string is not valid url format.
     */
    public static function safeURL(string $url_string): null|string
    {
        if (filter_var($url_string, FILTER_VALIDATE_URL)) {
            return $url_string;
        }

        return null;
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
}
