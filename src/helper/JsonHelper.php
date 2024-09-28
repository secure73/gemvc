<?php
namespace Gemvc\Helper;

class JsonHelper
{
    /**
     * @return null|array<mixed>
     */
    public static function validateJsonStringReturnArray(string $jsonStringToValidate): array|null
    {
        $result = json_decode($jsonStringToValidate, true);
        if (0 === json_last_error() && \is_array($result)) {
            return $result;
        }

        return null;
    }


    /**
     * @return string|false
     * @param  mixed $jsonStringToValidate
     * return json string if given string is valid json format, false otherwise
     */
    public static function validateJson(mixed $jsonStringToValidate): string|false
    {
        if(!is_string($jsonStringToValidate)) {
            return false;
        }
        $jsonStringToValidate = trim($jsonStringToValidate);
        $result = json_decode($jsonStringToValidate);
        if (0 === json_last_error()) {
            return $jsonStringToValidate;
        }
        return false; // Could enhance this to return an error message
    }


    /**
     * @return null|object
     */
    public static function validateJsonStringReturnObject(string $jsonStringToValidate): object|null
    {
        $result = json_decode($jsonStringToValidate);
        if (0 === json_last_error() && \is_object($result)) {
            return $result;
        }

        return null;
    }

    /**
     * @param mixed $data
     * @param int $options Optional JSON encoding options
     * @return string|false
     */
    public static function encodeToJson(mixed $data, int $options = 0): string|false
    {
        $json = json_encode($data, $options);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        return $json;
    }
}
