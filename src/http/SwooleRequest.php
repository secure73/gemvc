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

        try {
            if (!isset($swooleRequest->server['request_uri'])) {
                throw new \InvalidArgumentException("Missing request_uri - not a valid OpenSwoole request");
            }
            
            // Additional validation checks could go here
            
            $this->request->requestMethod = $swooleRequest->server['request_method'] ?? 'GET';
            $this->request->requestedUrl = $swooleRequest->server['request_uri'];
            $this->request->queryString = $swooleRequest->server['query_string'] ?? null;
            $this->request->remoteAddress = $swooleRequest->server['remote_addr'] . ':' . $swooleRequest->server['remote_port'];

            if (isset($swooleRequest->header['user-agent'])) {
                $this->request->userMachine = $swooleRequest->header['user-agent'];
            }

            $this->setData();
        } catch (\Exception $e) {
            // Set error in Request object for consistent handling
            $this->request->error = $e->getMessage();
            $this->request->response = Response::badRequest($e->getMessage());
            // Log the error
            error_log("SwooleRequest error: " . $e->getMessage());
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
        
        // Set request body data based on method
        switch ($this->request->requestMethod) {
            case 'POST':
                $this->setPost();
                break;
            case 'PUT':
                $this->setPut();
                break;
            case 'PATCH':
                $this->setPatch();
                break;
        }
        
        $this->setFiles();
        $this->setGet();
        $this->setCookies();
    }

    /**
     * Set the POST data from the incoming request.
     */
    private function setPost(): void
    {
        $contentType = $this->incomingRequestObject->header['content-type'] ?? '';
        
        // For standard form submissions
        $this->request->post = $this->incomingRequestObject->post ?? [];
        
        // Additionally parse JSON if content type indicates JSON
        if (empty($this->request->post) && 
            strpos($contentType, 'application/json') !== false) {
            $rawContent = $this->incomingRequestObject->rawContent() ?? '';
            $jsonData = json_decode($rawContent, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->request->post = $jsonData;
            }
        }
    }

    /**
     * Set the authorization token from the incoming request headers.
     */
    private function setAuthorizationToken(): void
    {
        if (isset($this->incomingRequestObject->header['authorization'])) {
            $this->request->authorizationHeader = $this->incomingRequestObject->header['authorization'];
            $this->request->jwtTokenStringInHeader = $this->parseAuthorizationToken($this->request->authorizationHeader);
        }
    }

    /**
     * Set the uploaded files from the incoming request.
     */
    private function setFiles(): void
    {
        $files = $this->incomingRequestObject->files ?? [];
        // Transform structure if needed to match PHP's $_FILES structure
        // that the Request class expects
        $this->request->files = $this->normalizeFilesArray($files);
    }

    private function normalizeFilesArray(array $files): array
    {
        // If empty, return empty array
        if (empty($files)) {
            return [];
        }
        
        $normalized = [];
        
        // Process the files array to match PHP's $_FILES structure
        foreach ($files as $key => $file) {
            if (is_array($file) && isset($file['name'])) {
                // Already in expected format
                $normalized[$key] = $file;
            } elseif (is_object($file) && isset($file->name)) {
                // Convert object to array format
                $normalized[$key] = [
                    'name' => $file->name,
                    'type' => $file->type ?? '',
                    'tmp_name' => $file->tmp_name ?? '',
                    'error' => $file->error ?? 0,
                    'size' => $file->size ?? 0
                ];
            }
        }
        
        return $normalized;
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
     * Set the PUT data from the incoming request.
     */
    private function setPut(): void
    {
        // OpenSwoole stores PUT data in the request body
        // Need to parse the raw content based on content type
        $rawContent = $this->incomingRequestObject->rawContent() ?? '';
        $contentType = $this->incomingRequestObject->header['content-type'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $this->request->put = json_decode($rawContent, true) ?? [];
        } else {
            // Parse form data format
            parse_str($rawContent, $putData);
            $this->request->put = $putData;
        }
    }

    /**
     * Set the PATCH data from the incoming request.
     */
    private function setPatch(): void
    {
        // Similar implementation to setPut
        // ...
    }

    /**
     * Parse the authorization token from the header.
     * @param string $authorizationHeader The full authorization header.
     * @return string|null The parsed token, if present.
     */
    public function parseAuthorizationToken(string $authorizationHeader): ?string
    {
        if (preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            return $matches[1]; // Return the token part
        }
        return null; // Or handle other types of tokens if necessary
    }
}
