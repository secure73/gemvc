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


    public function validatePost(array $rules): bool
    {
        if(!$this->hasRequiredPost($rules))
        {
            return false;
        }

        foreach ($rules as $field => $rule) {
            $rulesArr = explode('|', $rule);
            foreach ($rulesArr as $r) {
                if ($r === 'email' && !filter_var($this->post[$field], FILTER_VALIDATE_EMAIL)) {
                    $this->error = "$field is not a valid email";
                    return false;
                }
                if (strpos($r, 'min:') === 0 && strlen($this->post[$field]) < intval(substr($r, 4))) {
                    $this->error = "$field must be at least " . substr($r, 4) . " characters";
                    return false;
                }

                if (strpos($r, 'max:') === 0 && strlen($this->post[$field]) > intval(substr($r, 4))) {
                    $this->error = "$field must be at most " . substr($r, 4) . " characters";
                    return false;
                }
                if($r === 'int' && !is_numeric($this->post[$field]))
                {
                    $this->error = "$field must be an integer";
                    return false;
                }
                if($r === 'float' && !is_float($this->post[$field]))
                {
                    $this->error = "$field must be a float";
                    return false;
                }
                if($r === 'bool' && !is_bool($this->post[$field]))
                {
                    $this->error = "$field must be a boolean";
                    return false;
                }
                if($r === 'array' && !is_array($this->post[$field]))
                {
                    $this->error = "$field must be an array";
                    return false;
                }
                if($r === 'json' && !JsonHelper::validateJson($this->post[$field]))
                {
                    $this->error = "$field must be an object";
                    return false;
                }
            }
        }
        return true;
    }

    
   /**
    * @param array<string> $requieredPost
    */
    private function hasRequiredPost(array $requieredPost): bool
    {
        foreach ($requieredPost as $key => $item) {
            
            if (!isset($this->post[$key])) {
                $this->error = "post $key is required";
                return false;
            }
        }
        return true;
    }

}
