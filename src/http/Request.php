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
    public    array        $files;
    public    array        $post;
    public    array        $get;
    public    string       $userMachine;
    public    ?string      $requestMethod;


    public function __construct()
    {
        $this->id = TypeHelper::guid();
        $this->time = TypeHelper::timeStamp();
        $this->files = array();
        $this->post = array();
        $this->get = array();
        $this->error = null;
    }

    public function getId():string
    {
        return $this->id;
    }
}