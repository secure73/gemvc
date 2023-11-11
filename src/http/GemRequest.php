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
        foreach ($toValidatePost as $key => $item) {

            if ($key[0] !== '?') // it is required
            {
                if (!isset($this->post[$key])) {
                    $this->error = "post $key is required";
                    return false;
                }
            } //we are sure post is there
            else {
                $key = substr($key, 1);
                if (!isset($this->post[$key])) {
                    return true;
                }
            }

            if ($item === 'email' && !filter_var($this->post[$key], FILTER_VALIDATE_EMAIL)) {
                $this->error = "$key is not a valid email";
                return false;
            }
            if (strpos($item, 'min:') === 0 && strlen($this->post[$key]) < intval(substr($item, 4))) {
                $this->error = "$key must be at least " . substr($item, 4) . " characters";
                return false;
            }
            if (strpos($item, 'max:') === 0 && strlen($this->post[$key]) > intval(substr($item, 4))) {
                $this->error = "$key must be at most " . substr($item, 4) . " characters";
                return false;
            }
            if ($item === 'int' && !is_numeric($this->post[$key])) {
                $this->error = "$key must be an integer";
                return false;
            }
            if ($item === 'float' && !is_float($this->post[$key])) {
                $this->error = "$key must be a float";
                return false;
            }
            if ($item === 'bool' && !is_bool($this->post[$key])) {
                $this->error = "$key must be a boolean";
                return false;
            }
            if ($item === 'array' && !is_array($this->post[$key])) {
                $this->error = "$key must be an array";
                return false;
            }
            if ($item === 'json' && !JsonHelper::validateJson($this->post[$key])) {
                $this->error = "$key must be an object";
                return false;
            }
        }
    }
}
