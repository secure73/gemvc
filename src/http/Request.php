<?php

namespace Gemvc\Http;
use Gemvc\Helper\JsonHelper;

class Request
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
    public    ?string      $requestMethod;

    /**
     * @param object $incommingRequestObject
     */
    public function __construct(object $swooleRquest)
    {
        $this->time = microtime(true);

        $this->incommingRequestObject = $swooleRquest;
        if(isset($swooleRquest->server['request_uri']))
        {
            $this->requestMethod = $swooleRquest->server['request_method'];
            $this->requestedUrl = $swooleRquest->server['request_uri'];
            isset($swooleRquest->server['query_string']) ? $this->queryString = $swooleRquest->server['query_string'] : $this->queryString = null;
            $this->remoteAddress = $swooleRquest->server['remote_addr'] .':'. $swooleRquest->server['remote_port'];
            $this->userMachine = $swooleRquest->header['user-agent'];
            $this->setData();
        }
        else
        {
            $this->error = "incomming request is not openSwoole request";
        }

    }

    public function getOriginalSwooleRequest():object
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
        $this->service = $result[1];
        isset($result[2]) ? $this->controller = $result[2] : $this->controller = 'Index';
        isset($result[3]) ? $this->method = $result[3] : $this->controller = 'index';
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