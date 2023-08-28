<?php

namespace Gemvc\Core;
use Gemvc\Helper\JsonHelper;

class RequestDispatcher
{
    private   object      $incommingRequest;    
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

    public function __construct(object $sequest)
    {
        $this->time = microtime(true);
        $this->incommingRequest = $sequest;
        $this->requestedUrl = $sequest->server['request_uri'];
        $this->queryString = $sequest->server['query_string'];
        $this->remoteAddress = $sequest->server['remote_addr'] .':'. $sequest->server['remote_port'];
        $this->setData();

    }

    public function getOriginalRequest():object
    {
        return $this->incommingRequest;
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
    }


    private function setPayload():void
    {
        if(isset($this->incommingRequest->post['payload']))
        {
            $this->payload = json_decode($this->incommingRequest->post['payload']);
        }
    }

    private function setAuthorizationToken():void
    {
        if(isset($this->incommingRequest->header['authorization']))
        {
            $this->token = $this->incommingRequest->header['authorization'];
        }
    }

    private function setFind():void
    {
        if(isset($this->incommingRequest->post['find']))
        {
            $this->find = JsonHelper::validateJsonStringReturnObject(trim($this->incommingRequest->post['find']));
        }
    }

    private function setOrderBy():void
    {
        if(isset($this->incommingRequest->post['orderby']))
        {
            $this->orderby = JsonHelper::validateJsonStringReturnObject(trim($this->incommingRequest->post['orderby']));
        }
    }

    private function setCount():void
    {
        if(isset($this->incommingRequest->post['count']))
        {
            $count = trim($this->incommingRequest->post['count']);
            $this->count =  (is_numeric($count)) ? intval($count) : null;
        }
    }

    private function setPage():void
    {
        if(isset($this->incommingRequest->post['page']))
        {
            $this->page = JsonHelper::validateJsonStringReturnObject(trim($this->incommingRequest->post['page']));
        }
    }

    private function setFiles():void
    {
        if(isset($this->incommingRequest->files))
        {
            $this->files = $this->incommingRequest->files;
        }
    }
}
