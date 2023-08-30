<?php

namespace Gemvc\Core;
use Gemvc\Helper\JsonHelper;

class RequestDispatcher
{
    private   object       $incommingRequestObject;    
    public    string       $requestedUrl;
    public    ?string      $queryString;
    public    mixed        $payload = null;
    public    ?string      $error = "";
    public    int          $error_code = 0;
    public    ?object      $find;
    public    ?object      $orderby;
    public    ?object      $between;
    public    ?int         $page;
    public    ?int         $count;
    public    ?string      $token;
    public    string       $time;
    public    ?string      $remoteAddress;
    public    mixed        $files;
    public    ?int         $userId = null;
    public    string       $userMachine;
    public    ?string      $service;
    public    ?string      $controller;
    public    ?string      $method;


    public function __construct(object $sequest)
    {
        $this->time = microtime(true);
        $this->incommingRequestObject = $sequest;
        $this->requestedUrl = $sequest->server['request_uri'];
        $this->queryString = $sequest->server['query_string'];
        $this->remoteAddress = $sequest->server['remote_addr'] .':'. $sequest->server['remote_port'];
        $this->userMachine = $sequest->server['user_agent'];
        $this->setData();

    }

    public function getOriginalRequest():object
    {
        return $this->incommingRequestObject;
    }

    private function setData()
    {
        $this->setAuthorizationToken();
        $this->setPayload();
        $this->setFind();
        $this->setOrderBy();
        $this->setPage();
        $this->setCount();
        $this->setFiles();
        $this->setServiceRequest();
    }

    private function setServiceRequest():void
    {
        $result = explode('/',$this->requestedUrl);
        $this->service = $result[0];
        isset($result[1]) ? $this->controller = ucfirst($result[1]) : $this->controller = 'Index';
        isset($result[2]) ? $this->method = $result[2] : $this->controller = 'index';
    }


    private function setPayload():void
    {
        if(isset($this->incommingRequestObject->post['payload']))
        {
            $this->payload = json_decode($this->incommingRequestObject->post['payload']);
        }
    }

    private function setAuthorizationToken():void
    {
        if(isset($this->incommingRequestObject->header['authorization']))
        {
            $this->token = $this->incommingRequestObject->header['authorization'];
        }
    }

    private function setFind():void
    {
        if(isset($this->incommingRequestObject->post['find']))
        {
            $this->find = JsonHelper::validateJsonStringReturnObject(trim($this->incommingRequestObject->post['find']));
        }
    }

    private function setOrderBy():void
    {
        if(isset($this->incommingRequestObject->post['orderby']))
        {
            $this->orderby = JsonHelper::validateJsonStringReturnObject(trim($this->incommingRequestObject->post['orderby']));
        }
    }

    private function setCount():void
    {
        if(isset($this->incommingRequestObject->post['count']))
        {
            $count = trim($this->incommingRequestObject->post['count']);
            $this->count =  (is_numeric($count)) ? intval($count) : null;
        }
    }

    private function setPage():void
    {
        if(isset($this->incommingRequestObject->post['page']))
        {
            $this->page = JsonHelper::validateJsonStringReturnObject(trim($this->incommingRequestObject->post['page']));
        }
    }

    private function setFiles():void
    {
        if(isset($this->incommingRequestObject->files))
        {
            $this->files = $this->incommingRequestObject->files;
        }
    }
}
