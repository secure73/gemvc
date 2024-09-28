<?php

namespace Gemvc\Http;

use Gemvc\Http\Request;

/**
 * Class SwooleRequest
 * Handles the conversion of an OpenSwoole request to a Request object.
 */
class SwooleRequest
{
    public Request $request; 
    private object $incomingRequestObject;

    /**
     * SwooleRequest constructor.
     * @param object $swooleRequest The incoming OpenSwoole request object.
     * @throws \InvalidArgumentException If the provided request is not valid.
     */
    public function __construct(object $swooleRequest)
    {
        $this->request = new Request();
        $this->incomingRequestObject = $swooleRequest;

        if (isset($swooleRequest->server['request_uri'])) {
            $this->request->requestMethod = $swooleRequest->server['request_method'];
            $this->request->requestedUrl = $swooleRequest->server['request_uri'];
            $this->request->queryString = $swooleRequest->server['query_string'] ?? null;
            $this->request->remoteAddress = $swooleRequest->server['remote_addr'] . ':' . $swooleRequest->server['remote_port'];

            if (isset($swooleRequest->header['user-agent'])) {
                $this->request->userMachine = $swooleRequest->header['user-agent'];
            }

            $this->setData();
        } else {
            throw new \InvalidArgumentException("Incoming request is not an OpenSwoole request.");
        }
    }

    /**
     * Get the original Swoole request object.
     * @return object The original Swoole request.
     */
    public function getOriginalSwooleRequest(): object
    {
        return $this->incomingRequestObject;
    }

    /**
     * Set the request data from the incoming Swoole request.
     */
    private function setData(): void
    {
        $this->setAuthorizationToken();
        $this->setPost();
        $this->setFiles();
        $this->setGet();
        $this->setCookies();
    }

    /**
     * Set the POST data from the incoming request.
     */
    private function setPost(): void
    {
        $this->request->post = $this->incomingRequestObject->post ?? []; // Use null coalescing operator
    }

    /**
     * Set the authorization token from the incoming request headers.
     */
    private function setAuthorizationToken(): void
    {
        if (isset($this->incomingRequestObject->header['authorization'])) {
            $this->request->authorizationHeader = $this->incomingRequestObject->header['authorization'];
            // Uncomment if you implement parseAuthorizationToken
            // $this->request->token = $this->parseAuthorizationToken($this->request->authorizationHeader);
        }
    }

    /**
     * Set the uploaded files from the incoming request.
     */
    private function setFiles(): void
    {
        $this->request->files = $this->incomingRequestObject->files ?? []; // Use null coalescing operator
    }

    /**
     * Set the GET data from the incoming request.
     */
    private function setGet(): void
    {
        $this->request->get = $this->incomingRequestObject->get ?? []; // Use null coalescing operator
    }

    /**
     * Set the cookies from the incoming request.
     */
    private function setCookies(): void
    {
        $this->request->cookies = $this->incomingRequestObject->cookie ?? []; // Use null coalescing operator
    }

    /**
     * Parse the authorization token from the header.
     * @param string $authorizationHeader The full authorization header.
     * @return string|null The parsed token, if present.
     */
    private function parseAuthorizationToken(string $authorizationHeader): ?string
    {
        if (preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            return $matches[1]; // Return the token part
        }
        return null; // Or handle other types of tokens if necessary
    }
}
