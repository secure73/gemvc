<?php

namespace GemLibrary\Http;

use GemLibrary\Helper\StringHelper;
use GemLibrary\Http\GemRequest as HttpGemRequest;

class ApacheRequest
{
    public  HttpGemRequest $request; 

    public function __construct()
    {
        $this->request = new GemRequest();
        $this->request->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->request->userMachine = $_SERVER['HTTP_USER_AGENT'];
        $this->request->remoteAddress = $_SERVER['REMOTE_ADDR'];
        $this->request->requestedUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $this->request->queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
        $this->request->post = $_POST;
        $this->request->get = $_GET;
        if (isset($_FILES['file'])) {
           $this->request->files = $_FILES['file'];
        }
        $this->setAuthHeader();
    }

    private function setAuthHeader():void
    {
        $this->request->authorizationHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? StringHelper::sanitizedString($_SERVER['HTTP_AUTHORIZATION']) : null;
        // If the "Authorization" header is empty, you may want to check for the "REDIRECT_HTTP_AUTHORIZATION" header as well.
        if (!$this->request->authorizationHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $this->request->authorizationHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
    }
}