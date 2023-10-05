<?php

namespace Gemvc\Http;
use Gemvc\Helper\TypeHelper;
use TypeError;

class Request
{
    public    string       $requestedUrl;
    public    ?string      $queryString;
    public    ?string      $error;
    public    ?string      $authorizationHeader;
    public    ?string      $remoteAddress;
    public    mixed        $files;
    public    mixed        $post;
    public    mixed        $get;
    public    string       $userMachine;
    public    ?string      $requestMethod;
    private   string       $id;
    private   string       $time;
    private   float        $start_execution_time;


    public function __construct()
    {
        $this->start_execution_time = microtime(true);
        $this->id = TypeHelper::guid();
        $this->time = TypeHelper::timeStamp();
        $this->error = null;
    }

    public function getId():string
    {
        return $this->id;
    }
    public function getTime():string
    {
        return $this->time;
    }

    public function getStartExecutionTime():float
    {
        return  $this->start_execution_time;
    }
}