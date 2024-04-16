<?php

namespace GemLibrary\Http;

use GemLibrary\Helper\JsonHelper;
use GemLibrary\Helper\TypeHelper;

class GemRequest
{
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
        $this->start_exec = microtime(true);
        $this->id = TypeHelper::guid();
        $this->time = TypeHelper::timeStamp();
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
    
      foreach ($toValidatePost as $validation_key => $validationString) {
        $isRequired = (substr($validation_key, 0, 1) === '?') ? false : true; // Use ternary operator
        $validation_key = ltrim($validation_key, '?'); // Remove optional prefix
    
        if ($isRequired && (!isset($this->post[$validation_key]) || empty($this->post[$validation_key]))) {
          $errors[] = "Missing required field: $validation_key";
          continue; // Skip to the next iteration
        }
    
        if (isset($this->post[$validation_key]) && !empty($this->post[$validation_key])) {
          $validationResult = $this->checkPostKeyValue($validation_key, $validationString);
          if (!$validationResult) {
            $errors[] = "Invalid value for field: $validation_key";
          }
        }
      }
      if (count($errors) > 0) {
        foreach($errors as $error)
        {
            $this->error .= $error.', '; // Combine errors into a single string
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

    public function forwardToRemoteApi(string $remoteApiUrl): JsonResponse
    {
        $jsonResponse = new JsonResponse();
        $ch = curl_init($remoteApiUrl);
        if ($ch === false) {
            $jsonResponse->create(500, [], 0, "remote api $remoteApiUrl is not responding");
            return $jsonResponse;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        if (is_string($this->authorizationHeader)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $this->authorizationHeader]);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, 'gemserver');

        if (isset($this->files)) {

            foreach ($this->files as $key => $value) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $value);
            }
        }
        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response || !is_string($response)) {
            $jsonResponse->create(500, [], 0, 'remote api is not responding');
            return $jsonResponse;
        }
        if (!JsonHelper::validateJson($response)) {
            $jsonResponse->create(500, [], 0, 'remote api is not responding with valid json');
            return $jsonResponse;
        }
        $response = json_decode($response);
        /**@phpstan-ignore-next-line */
        $jsonResponse->create($response->http_response_code, $response->data, $response->count, $response->service_message);
        return $jsonResponse;
    }

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
}
