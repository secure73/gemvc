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
     * @param array<mixed> $toValidatePost
     * @return bool
     */
    public function definePostSchema(array $toValidatePost): bool
    {
        foreach ($toValidatePost as $key => $validationString) {
            if ($key[0] !== '?') // it is required
            {
                if (!isset($this->post[$key])) {
                    $this->error = "post $key is required";
                    return false;
                }
            } //we are sure post is there
            else {
                $key = substr($key, 0);
                if (!isset($this->post[$key])) {
                    return true;
                }
            }

            switch($validationString)
            {
                case 'string':
                    if(!is_string($this->post[$key]))
                    {
                        $this->error = "$key must be a string";
                        return false;
                    }
                    break;
                case 'int':
                    if(!is_numeric($this->post[$key]))
                    {
                        $this->error = "$key must be an integer";
                        return false;
                    }
                    break;
                case 'float':
                    if(!is_float($this->post[$key]))
                    {
                        $this->error = "$key must be a float";
                        return false;
                    }
                    break;
                case 'bool':
                    if(!is_bool($this->post[$key]))
                    {
                        $this->error = "$key must be a boolean";
                        return false;
                    }
                    break;
                case 'array':
                    if(!is_array($this->post[$key]))
                    {
                        $this->error = "$key must be an array";
                        return false;
                    }
                    break;
                case 'json':
                    if(!JsonHelper::validateJson($this->post[$key]))
                    {
                        $this->error = "$key must be an object";
                        return false;
                    }
                    break;
                case 'email':
                    if(!filter_var($this->post[$key], FILTER_VALIDATE_EMAIL))
                    {
                        $this->error = "$key is not a valid email";
                        return false;
                    }
                    break;
                default:
                        $this->error = "unknown validation  $key &  $validationString";
                        return false;
            }
        }
        return false;
    }
}
