<?php

namespace GemLibrary\Http;

use GemLibrary\Helper\JsonHelper;
use GemLibrary\Helper\TypeHelper;

class GemRequest
{
    public    string       $requestedUrl;
    public    ?string      $queryString;
    public    ?string      $error;
    public    ?string      $authorizationHeader;
    public    ?string      $remoteAddress;
    /**
     * @var array<mixed>
     */
    public    array        $files;
    /**
     * @var array<mixed>
     */
    public    array        $post;
    public    mixed        $get;
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

    public function getError():string|null
    {
        return this->error;
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
     * @param array<string> $toValidatePost
     * @return bool
     */
    public function definePostSchema(array $toValidatePost): bool
    {
        foreach ($toValidatePost as $key => $validationString) {
            $isRequired = $this->isRequired($key);
            if ($isRequired) {
                if (!isset($this->post[$key]) || empty($this->post[$key])) {
                    $this->error = "post $key is required";
                    return false;
                } else {
                    if (!$this->checkPostKeyValue($key, $validationString)) {
                        return false;
                    }
                }
            } else {
                $key = substr($key, 1);
                if (isset($this->post[$key]) && !empty($this->post[$key])) {
                    if (!$this->checkPostKeyValue($key, $validationString)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function setPostToObject(object $class):bool
    {
        try{
            foreach ($this->post as $key => $value) {
                if (property_exists($class, $key)) {
                    $class->$key = $value;
                }
        }
        return true;
        }
        catch(\Exception $e)
        {
            $this->error = $e->getMessage();
        }
        return false;
    }

    private function isRequired(string $post_key): bool
    {
        if ($post_key[0] !== '?') // it is required
        {
            return true;
        }
        return false;
    }

    private function checkPostKeyValue(string $key, string $validationString): bool
    {

        if (!$this->checkValidationTypes($validationString)) {
            return false;
        }


        $result = match ($validationString) {
            'string' => is_string($this->post[$key]),
            'int' => is_numeric($this->post[$key]),
            'float' => is_float($this->post[$key]),
            'bool' => is_bool($this->post[$key]),
            'array' => is_array($this->post[$key]),
            /** @phpstan-ignore-next-line */
            'json' => JsonHelper::validateJson($this->post[$key]) ? true : false,
            'email' => filter_var($this->post[$key], FILTER_VALIDATE_EMAIL),
            default => false
        };
        if ($result == false) {
            $this->error = "the $key must be $validationString";
        }

        return true;
    }

    private function checkValidationTypes(string $validationString):bool
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
