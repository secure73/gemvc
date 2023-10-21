<?php

namespace Gemvc\Http;
use Gemvc\Http\Request;

class ApacheRequest
{
    public  GemRequest $request; 

    public function __construct()
    {
        $this->request = new GemRequest();
        $this->request->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->request->userMachine = $_SERVER['HTTP_USER_AGENT'];
        $this->request->remoteAddress = $_SERVER['REMOTE_ADDR'];
        $this->request->requestedUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $this->request->queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
        if (isset($_FILES['file'])) {
           $this->request->files = $_FILES['file'];
        }
        if(isset($_POST))
        {
            $this->request->post = $_POST;
        }
        if(isset($_GET))
        {
            $this->request->get = $_GET;
        }
        $this->setAuthHeader();
    }

    private function setAuthHeader()
    {
        $this->request->authorizationHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        // If the "Authorization" header is empty, you may want to check for the "REDIRECT_HTTP_AUTHORIZATION" header as well.
        if (empty($authorizationHeader) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $this->request->authorizationHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
    }
}