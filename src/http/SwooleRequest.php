<?php

namespace Gemvc\Http;

use Gemvc\Helper\StringHelper;
use Gemvc\Http\Request;

class SwooleRequest
{
    public   Request $request; 
    private  object  $incommingRequestObject;
       
    /**
     * @param object $swooleRquest
     */
    public function __construct(object $swooleRquest)
    {
        $this->request = new Request();
        $this->incommingRequestObject = $swooleRquest;
        if(isset($swooleRquest->server['request_uri'])) {
            $this->request->requestMethod = $swooleRquest->server['request_method'];
            $this->request->requestedUrl = $swooleRquest->server['request_uri'];
            isset($swooleRquest->server['query_string']) ? $this->request->queryString = $swooleRquest->server['query_string'] : $this->request->queryString = null;
            $this->request->remoteAddress = $swooleRquest->server['remote_addr'] .':'. $swooleRquest->server['remote_port'];
            if(isset($swooleRquest->header['user-agent'])) {
                $this->request->userMachine = $swooleRquest->header['user-agent'];
            }
            $this->setData();
        }
        else
        {
            $this->request->error = "incomming request is not openSwoole request";
        }
    }

    public function getOriginalSwooleRequest():object
    {
        return $this->incommingRequestObject;
    }

    private function setData():void
    {
        $this->setAuthorizationToken();
        $this->setPost();
        $this->setFiles();
        $this->setGet();
    }


    private function setPost():void
    {
        if(isset($this->incommingRequestObject->post)) {
            $this->request->post = $this->incommingRequestObject->post;
        }
    }


    private function setAuthorizationToken():void
    {
        if(isset($this->incommingRequestObject->header['authorization'])) {
            $this->request->authorizationHeader = $this->incommingRequestObject->header['authorization'];
        }
    }

    private function setFiles():void
    {
        if(isset($this->incommingRequestObject->files)) {
            $this->request->files = $this->incommingRequestObject->files;
        }
    }

    private function setGet():void
    {
        if(isset($this->incommingRequestObject->get)) {
            $this->request->get = $this->incommingRequestObject->get;
        }
    }
}
