<?php
namespace Gemvc\Helper;
class WebHelper{

    /**
     * @param string $url
     * @return bool
     * if given string is has valid url format return true
     */
    public static function isValidUrl(string $url):bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * @param string $email
     * @return bool
     * if given string is has valid email format return true
     */
    public static function isValidEmail(string $email) {
        // Use filter_var with FILTER_VALIDATE_EMAIL
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }


}