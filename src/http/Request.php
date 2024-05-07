<?php

namespace GemLibrary\Http;

use GemLibrary\Helper\JsonHelper;
use GemLibrary\Helper\TypeHelper;
use GemLibrary\Helper\WebHelper;

class Request
{
    public    ?string      $jwtTokenStringInHeader;
    public    string       $requestedUrl;
    public    ?string      $queryString;
    public    ?string      $error;
    /**
     * @var null|string|array<string>
     */
    public    null|string|array      $authorizationHeader;
    public    ?string      $remoteAddress;
    /**
     * @var array<mixed>
     */
    public    array        $files;
    /**
     * @var array<mixed>
     */
    public    array        $post;

    /**
     * @var array<mixed>
     */
    public null|array      $put;

    /**
     * @var array<mixed>
     */
    public null|array      $patch;

    /**
     * @var string|array<mixed>
     */
    public    string|array        $get;
    public    string       $userMachine;
    public    ?string      $requestMethod;
    private   string       $id;
    private   string       $time;
    private   float        $start_exec;


    public function __construct()
    {
        $this->error = "";
        $this->authorizationHeader = null;
        $this->jwtTokenStringInHeader = null;
        $this->requestMethod = null;
        $this->start_exec = microtime(true);
        $this->id = TypeHelper::guid();
        $this->time = TypeHelper::timeStamp();
        $this->extractPureJWTTokenFromHeader();
    }

    public function __set(string $key , mixed $value):void
    {
        $this->$key = $value;
    }

    public function __get(string $name):mixed
    {
        return $this->$name;
    }

    public function getError(): string|null
    {
        return $this->error;
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getTime(): string
    {
        return $this->time;
    }

    public function getStartExecutionTime(): float
    {
        return  $this->start_exec;
    }


    /**
     * @param array<string> $toValidatePost  Define Post Schema to validation
     * @return bool
     * validatePosts(['email'=>'email' , 'id'=>'int' , '?name' => 'string'])
     * @help : ?name means it is optional
     * @in case of false $this->error will be setted
     */
    public function definePostSchema(array $toValidatePost): bool
    {
        $errors = []; // Initialize an empty array to store errors
        $requires = [];
        $optionals = [];

        foreach ($toValidatePost as $validation_key => $validationString) {
            (substr($validation_key, 0, 1) === '?') ? $requires[$validation_key] = $validationString : $optionals[ltrim($validation_key, '?')] = $validationString; // Use ternary operator
        }
        foreach($this->post as $postName => $postValue) { //if there is any post other than defined schma, instanlty breake the process and return false.
            if(!array_key_exists($postName ,$requires) || !array_key_exists($postName,$optionals)) {
                $errors[$postName] = "unwanted post $postName";
                unset($this->post[$postName]);
            }
            return false;
        }

        foreach($requires as $validation_key => $validation_value) {      //now only check existance of requires post 
            if ((!isset($this->post[$validation_key]) || empty($this->post[$validation_key]))) {
                $errors[] = "Missing required field: $validation_key";
                continue; // Skip to the next iteration
            }
        }
        if (count($errors) > 0) { //if requires not exists , stop process and return false
            foreach ($errors as $error) {
                $this->error .= $error . ', '; // Combine errors into a single string
            }
            return false;
        }
        foreach($requires as $validation_key => $validationString) { //now validate requires post Schema
            $validationResult = $this->checkPostKeyValue($validation_key, $validationString);
                if (!$validationResult) {
                    $errors[] = "Invalid value for field: $validation_key";
            }
        }
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->error .= $error . ', '; // Combine errors into a single string
            }
            return false;
        }

        foreach($optionals as $optionals_key => $optionals_value) { //check optionals if post exists and not null then do check
        
            if (isset($this->post[$optionals_key]) && !empty($this->post[$optionals_key])) {
                $validationResult = $this->checkPostKeyValue($optionals_key, $optionals_value);
                if (!$validationResult) {
                    $errors[] = "Invalid value for field: $optionals_key";
                }
            }
        }
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->error .= $error . ', '; // Combine errors into a single string
            }
            return false;
        }
        return true;
    }

    public function setPostToObject(object $class): bool
    {
        try {
            foreach ($this->post as $key => $value) {
                if (property_exists($class, $key)) {
                    $class->$key = $value;
                }
            }
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
        return false;
    }

    /**
     * Validates string lengths in a dictionary against min and max constraints.
     *
     * @param array<string, string> $stringPosts A dictionary where keys are strings and values are strings in the format "key:min-value|max-value" (optional).
     * @return bool True if all strings pass validation, False otherwise.
     */
    public function validateStringPosts(array $stringPosts): bool
    {
        foreach ($stringPosts as $key => $value) {
            // Check if POST key exists
            if (!isset($this->post[$key])) {
                $this->error = "Missing POST key '$key'";
                return false;
            }

            $constraints = explode('|', $value);
            // Ensure constraints are in the expected format (key:min-value|max-value)
            if (count($constraints) !== 2) {
                $this->error = "Invalid format for key $key: expected 'key:min-value|max-value'";
                return false;
            }
            $min_condition = $constraints[0];
            $max_condition = $constraints[1];

            $min  = explode('-', $min_condition);
            if (count($min) !== 2) {
                $min = 0;
            } else {
                $min = (int)$min[1];
            }

            $max  = explode('-', $max_condition);
            if (count($max) !== 2) {
                $max = 9999999999;
            } else {
                $max = (int)$max[1];
            }
            // Validate string length against min and max constraints (assuming $this->post[$key] is a string)
            $stringLength = strlen($this->post[$key]);/** @phpstan-ignore-line */
            
            if (!($min <= $stringLength && $stringLength <= $max)) {
                $this->error = "String length for post '$key' is ({$stringLength}) . it is outside the range ({$min}-{$max})";
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string> $postKeys Key-value pairs where key is the POST data key and value is the corresponding object property
     * @param object $class The object to populate with POST data
     * @return bool True on success, false on failure with an error message set in `$this->error`
     */
    public function mapPostToObjectxxx(array $postKeys, object $class): bool
    {
        foreach ($postKeys as $postKey => $classProperty) {
            if (!isset($this->post[$postKey])) {
                $this->error = "POST key '$postKey' is not found in request";
                return false;
            }

            if (!property_exists($class, $classProperty)) {
                $this->error = "Target Class has no '$classProperty' as property";
                return false;
            }

            $propertyValue = $this->post[$postKey];

            // Validate property type
            $isValidType = $this->validatePropertyType($classProperty, $propertyValue);
            if (!$isValidType) {
                $this->error = "Invalid value type for property '$classProperty'";
                return false;
            }

            // Convert value to target type if needed
            $convertedValue = $this->convertToTargetType($propertyValue, $classProperty);

            try {
                $class->$classProperty = $convertedValue;
            } catch (\Exception $e) {
                $this->error = "Error setting property '$classProperty': " . $e->getMessage();
                return false;
            }
        }

        return true;
    }


    public function forwardToRemoteApi(string $remoteApiUrl): JsonResponse
    {

        $jsonResponse = new JsonResponse();
        $caller = new ApiCall();
        $caller->post = $this->post;
        $caller->files = $this->files;
        $caller->authorizationHeader = $this->authorizationHeader;

        $response = $caller->call($remoteApiUrl);
        if (!$response) {
            $jsonResponse->create($caller->http_response_code, null, 0, $caller->error);
            return $jsonResponse;
        }
        $response = json_decode($response);
        $jsonResponse->create($caller->http_response_code, $response);
        return $jsonResponse;
    }

    
    public function extractPureJWTTokenFromHeader():void
    {
        if (!$this->authorizationHeader) {
            $this->error = 'no token found in header';
            return;
        }
        if (!is_string($this->authorizationHeader)) {
            $this->error = 'jwt token shall be type of string';
            return;
        }
        $pureToken = WebHelper::BearerTokenPurify($this->authorizationHeader);
        if (!$pureToken) {
            $this->error = 'given token is not confirmed with jwt schema';
            return;
        }
        $this->jwtTokenStringInHeader = $pureToken;
    }
    //----------------------------PRIVATE FUNCTIONS---------------------------------------

    

    private function checkPostKeyValue(string $key, string $validation): bool
    {
        // General validation (assumed in checkValidationTypes)
        if (!$this->checkValidationTypes($validation)) {
            return false;
        }

        // Specific data type validation (using a dictionary for readability)
        $validationMap = [
            'string' => is_string($this->post[$key]),
            'int' => is_numeric($this->post[$key]),
            'float' => is_float($this->post[$key]),
            'bool' => is_bool($this->post[$key]),
            'array' => is_array($this->post[$key]),
            'json' => (JsonHelper::validateJson($this->post[$key]) ? true : false),
            'email' => filter_var($this->post[$key], FILTER_VALIDATE_EMAIL) !== false, // Explicit false check for email
        ];

        // Validate data type based on validationMap
        $result = isset($validationMap[$validation]) ? $validationMap[$validation] : false;

        if (!$result) {
            $this->error = "The field '$key' must be of type '$validation'"; // More specific error message
        }

        return $result;
    }

    private function checkValidationTypes(string $validationString): bool
    {
        $validation = [
            'string',
            'int',
            'float',
            'bool',
            'array',
            'json',
            'email'
        ];
        if (!in_array($validationString, $validation)) {
            $this->error = "unvalid type of validation for $validationString";
            return false;
        }
        return true;
    }

    /**
     * Validates if a value matches the expected type for a property.
     *
     * @param string|null $propertyType The expected type of the property (e.g., "string", "int", "MyClass")
     * @param mixed $value The value to validate
     * @return bool True if the value matches the property type, false otherwise
     */
    private function validatePropertyType(?string $propertyType, mixed $value): bool
    {
        if ($propertyType === null) {
            // Allow any type if property type is not specified
            return true;
        }

        switch ($propertyType) {
            case 'string':
                return is_string($value);
            case 'int':
                return is_numeric($value) && is_int($value); // Ensure integer type
            case 'float':
                return is_float($value);
            case 'bool':
                return is_bool($value);
            case 'array':
                return is_array($value);
            default:
                $this->error = "unsupported type";
                return false;
        }
    }

    /**
     * Attempts to convert a value to the target type, if possible.
     *
     * @param mixed $value The value to convert
     * @return mixed The converted value or the original value if conversion is not possible
     * @throws \InvalidArgumentException If conversion fails due to incompatible types
     */
    private function convertToTargetType(mixed $value, string|null $targetType): mixed
    {

        if (is_null($targetType)) {
            // Allow any type if no target type specified
            return $value;
        }

        switch ($targetType) {
            case 'int':
                if (is_numeric($value)) {
                    return (int) $value; // Convert to integer
                }
                break;
            case 'float':
                if (is_numeric($value)) {
                    return (float) $value; // Convert to float
                }
                break;
            case 'bool':
                if (is_string($value) && in_array(strtolower($value), ['true', 'false', '1', '0'])) {
                    return (bool) $value; // Convert to boolean
                }
                break;
            case 'string':
                // String is the default type, no conversion needed
                return  $value;
            default:
                // Handle custom object types (optional)
                if (class_exists($targetType)) {
                    // Implement logic to convert to the custom object type (if possible)
                    // You might need additional libraries or custom conversion functions here
                    $this->error = "Conversion to object type '$targetType' not supported";
                } else {
                    $this->error = "Unsupported target type: '$targetType'";
                }
        }

        throw new \InvalidArgumentException("Could not convert value to target type: '$targetType'");
    }
}
