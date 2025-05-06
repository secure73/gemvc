<?php

namespace Gemvc\Http;

use Gemvc\Http\Request;

/**
 * Class SwooleRequest
 * Handles the conversion of an OpenSwoole request to a Request object.
 * Includes input sanitization to prevent security vulnerabilities.
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
            $this->request->requestedUrl = $this->sanitizeRequestURI($swooleRequest->server['request_uri']);
            $this->request->queryString = isset($swooleRequest->server['query_string']) ? 
                $this->sanitizeInput($swooleRequest->server['query_string']) : null;
            $this->request->remoteAddress = $swooleRequest->server['remote_addr'] . ':' . $swooleRequest->server['remote_port'];

            if (isset($swooleRequest->header['user-agent'])) {
                $this->request->userMachine = $this->sanitizeInput($swooleRequest->header['user-agent']);
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
        $postData = $this->incomingRequestObject->post ?? [];
        $this->request->post = $this->sanitizeData($postData);
        
        // Additionally parse JSON if content type indicates JSON
        if (empty($this->request->post) && 
            strpos($contentType, 'application/json') !== false) {
            $rawContent = $this->incomingRequestObject->rawContent() ?? '';
            $jsonData = json_decode($rawContent, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->request->post = $this->sanitizeData($jsonData);
            }
        }
    }

    /**
     * Set the authorization token from the incoming request headers.
     */
    private function setAuthorizationToken(): void
    {
        if (isset($this->incomingRequestObject->header['authorization'])) {
            $authHeader = $this->sanitizeInput($this->incomingRequestObject->header['authorization']);
            $this->request->authorizationHeader = $authHeader;
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

    /**
     * Normalize the files array to match PHP's $_FILES structure.
     * 
     * @param array $files The files array from Swoole request
     * @return array Normalized files array
     */
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
                // Sanitize file name if it's a string
                if (is_string($file['name'])) {
                    $normalized[$key]['name'] = $this->sanitizeInput($file['name']);
                }
                if (is_string($file['type'])) {
                    $normalized[$key]['type'] = $this->sanitizeInput($file['type']);
                }
            } elseif (is_object($file) && isset($file->name)) {
                // Convert object to array format
                $normalized[$key] = [
                    'name' => is_string($file->name) ? $this->sanitizeInput($file->name) : $file->name,
                    'type' => isset($file->type) && is_string($file->type) ? $this->sanitizeInput($file->type) : '',
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
        $getData = $this->incomingRequestObject->get ?? [];
        $this->request->get = $this->sanitizeData($getData);
    }

    /**
     * Set the cookies from the incoming request.
     */
    private function setCookies(): void
    {
        $cookieData = $this->incomingRequestObject->cookie ?? [];
        $sanitizedCookies = $this->sanitizeData($cookieData);
        $this->request->cookies = json_encode($sanitizedCookies);
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
            $jsonData = json_decode($rawContent, true) ?? [];
            $this->request->put = $this->sanitizeData($jsonData);
        } else {
            // Parse form data format
            parse_str($rawContent, $putData);
            $this->request->put = $this->sanitizeData($putData);
        }
    }

    /**
     * Set the PATCH data from the incoming request.
     */
    private function setPatch(): void
    {
        // Similar implementation to setPut
        $rawContent = $this->incomingRequestObject->rawContent() ?? '';
        $contentType = $this->incomingRequestObject->header['content-type'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $jsonData = json_decode($rawContent, true) ?? [];
            $this->request->patch = $this->sanitizeData($jsonData);
        } else {
            // Parse form data format
            parse_str($rawContent, $patchData);
            $this->request->patch = $this->sanitizeData($patchData);
        }
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

    /**
     * Sanitize input string to prevent XSS attacks.
     * 
     * @param mixed $input The input to sanitize
     * @return mixed Sanitized input
     */
    private function sanitizeInput(mixed $input): mixed
    {
        if (!is_string($input)) {
            return $input;
        }
        return filter_var(trim($input), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    /**
     * Sanitize array data recursively.
     * 
     * @param array $data The data array to sanitize
     * @return array Sanitized data array
     */
    private function sanitizeData(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize the request URI.
     * 
     * @param string $uri The URI to sanitize
     * @return string Sanitized URI
     */
    private function sanitizeRequestURI(string $uri): string
    {
        $sanitizedURI = trim($uri);
        if (!filter_var($sanitizedURI, FILTER_SANITIZE_URL)) {
            return '';
        }
        return $sanitizedURI;
    }
}
