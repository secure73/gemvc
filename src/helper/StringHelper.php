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
}
