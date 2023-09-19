<?php

namespace Gemvc\Helper;

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
}
