<?php
namespace Gemvc\Helper;
class TypeChecker
{

    /**
     * Checks and validates data based on a given type.
     *
     * @param mixed $type string,int,float,bool,array,json,email,date,integer,number,float,bool,boolean,array,object,callable,resource,null,email,url,date,datetime,json,ip,ipv4,ipv6,Class Names (as strings or objects)
     * @param mixed $value The value to check.
     * @param array<string> $options Optional parameters for specific types (e.g., min/max length for strings, date format).
     *
     * @return bool True if the value matches the type and any provided options, false otherwise.
     * @example TypeHelper::checkType('datetime', '10/27/2023 10:00:00', ['format' => 'm/d/Y H:i:s']);
     * @example TypeHelper::checkType('url', 'https://www.example.com');
     * @example TypeHelper::checkType('int', 5, ['min' => 6]);
     * @example TypeHelper::checkType('string', 'test', ['minLength' => 5]);
     * @example TypeHelper::checkType('string', 'Hello World', ['maxLength' => 10]);
     * @example TypeHelper::checkType('string', 'World', ['regex' => '/^H/']);
     * @endcode TypeHelper::checkType('ip', '2001:0db8:85a3:0000:0000:8a2e:0370:7334');
     */
    public static function check(mixed $type, mixed $value, array $options = []): bool
    {
        if (is_string($type)) {
            switch (strtolower($type)) {
                case 'string':
                    return self::checkString($value, $options);
                case 'int':
                    return is_numeric($value);
                case 'integer':
                    return is_numeric($value);
                case 'number':
                    return is_numeric($value);
                case 'float':
                    return self::checkFloat($value, $options);
                case 'double':
                    return self::checkFloat($value, $options);
                case 'bool':
                    return is_bool($value);
                case 'boolean':
                    return is_bool($value);
                case 'array':
                    return is_array($value);
                case 'object':
                    return is_object($value);
                case 'callable':
                    return is_callable($value);
                case 'resource':
                    return is_resource($value);
                case 'null':
                    return is_null($value);
                case 'email':
                    return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
                case 'url':
                    return (bool) filter_var($value, FILTER_VALIDATE_URL);
                case 'date':
                    return self::checkDate($value, $options);
                case 'datetime':
                    return self::checkDateTime($value, $options);
                case 'json':
                    return self::checkJson($value);
                case 'ip':
                    return (bool) filter_var($value, FILTER_VALIDATE_IP);
                case 'ipv4':
                    return (bool) filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
                case 'ipv6':
                    return (bool) filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
                default:
                    if (class_exists($type) || interface_exists($type)) {
                        return $value instanceof $type;
                    }
                    return false; // Unknown type
            }
        } elseif (is_object($type)) {
            return $value instanceof $type;
        } else {
            return false; // Invalid type for $type argument
        }
    }


    /**
     * Checks if a value is a string and meets the given options.
     *
     * @param mixed $value The value to check.
     * @param array<string> $options The options to check against.
     * @return bool True if the value is a string and meets the options, false otherwise.
     */
    private static function checkString(mixed $value, array $options): bool
    {
        if (!is_string($value)) {
            return false;
        }
        if (isset($options['minLength']) && strlen($value) < $options['minLength']) {
            return false;
        }
        if (isset($options['maxLength']) && strlen($value) > $options['maxLength']) {
            return false;
        }
        if (isset($options['regex']) && !preg_match($options['regex'], $value)) {
            return false;
        }
        return true;
    }

    /**
     * Checks if a value is an integer and meets the given options.
     * @param mixed $value The value to check.
     * @return bool True if the value is an integer and meets the options, false otherwise.
     */
    private static function checkInteger(mixed $value): bool
    {
        return is_numeric($value);
    }

    /**
     * Checks if a value is a float and meets the given options.
     *
     * @param mixed $value The value to check.
     * @param array<string> $options The options to check against.
     * @return bool True if the value is a float and meets the options, false otherwise.
     */
    private static function checkFloat(mixed $value, array $options): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_FLOAT)) {
            return false;
        }
        if (isset($options['min']) && $value < $options['min']) {
            return false;
        }
        if (isset($options['max']) && $value > $options['max']) {
            return false;
        }
        return true;
    }

    /**
     * Checks if a value is a date string and meets the given format.
     *
     * @param mixed $value The value to check.
     * @param array<string> $options The options to check against.
     * @return bool True if the value is a date string and meets the format, false otherwise.
     */
    private static function checkDate($value, array $options): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $format = $options['format'] ?? 'Y-m-d'; // Default format
        $d = \DateTime::createFromFormat($format, $value);
        return $d && $d->format($format) === $value;
    }

    /**
     * Checks if a value is a datetime string and meets the given format.
     *
     * @param mixed $value The value to check.
     * @param array<string> $options The options to check against.
     * @return bool True if the value is a datetime string and meets the format, false otherwise.
     */
    private static function checkDateTime(mixed $value, array $options): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $format = $options['format'] ?? 'Y-m-d H:i:s'; // Default format
        $d = \DateTime::createFromFormat($format, $value);
        return $d && $d->format($format) === $value;
    }

    /**
     * Checks if a value is a valid JSON string.
     *
     * @param mixed $value The value to check.
     * @return bool True if the value is a valid JSON string, false otherwise.
     */
    private static function checkJson(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        try {
            json_decode($value, null, 512, JSON_THROW_ON_ERROR);
            return true;
        } catch (\JsonException $e) {
            return false;
        }
    }

}

