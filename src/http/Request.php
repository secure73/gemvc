<?php

namespace Gemvc\Http;
use Gemvc\Helper\TypeHelper;
use TypeError;

class Request
{
    private   string       $id;
    public    string       $requestedUrl;
    public    ?string      $queryString;
    public    ?string      $error;
    public    ?string      $authorizationHeader;
    public    string       $time;
    public    ?string      $remoteAddress;
    public    mixed        $files;
    public    mixed        $post;
    public    mixed        $get;
    public    string       $userMachine;
    public    ?string      $requestMethod;


    public function __construct()
    {
        $this->id = TypeHelper::guid();
        $this->time = TypeHelper::timeStamp();
        $this->error = null;
    }

    public function getId():string
    {
        return $this->id;
    }
}