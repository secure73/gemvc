<?php
//because die() is not available in Swoole
namespace App\Core;

use Gemvc\Http\Request;
use Gemvc\Http\Response;
use Gemvc\Http\JsonResponse;

/**
 * SwooleApiService - OpenSwoole-compatible API service base class
 * 
 * This class replaces the Gemvc\Core\ApiService class to work with Swoole's
 * persistent process model by returning responses instead of using die()
 */
class SwooleApiService
{
    protected Request $request;
    public ?string $error;

    /**
     * Constructor
     * 
     * @param Request $request The HTTP request object
     */
    public function __construct(Request $request)
    {
        $this->error = null;
        $this->request = $request;
    }

    /**
     * Default index method
     * 
     * @return JsonResponse Welcome response
     */
    public function index(): JsonResponse
    {
        $name = get_class($this);
        $name = explode('\\', $name)[2];
        return Response::success("Welcome to $name service");
    }

    /**
     * Validates POST data against a schema
     * 
     * @param array<string> $post_schema Validation schema
     * @return JsonResponse|null Error response or null if validation passes
     */
    protected function validatePosts(array $post_schema): ?JsonResponse
    {
        if (!$this->request->definePostSchema($post_schema)) {
            return Response::badRequest($this->request->error);
        }
        return null;
    }

    /**
     * Validates string lengths in POST data
     * 
     * @param array<string> $post_string_schema Validation schema with min/max lengths
     * @return JsonResponse|null Error response or null if validation passes
     */
    protected function validateStringPosts(array $post_string_schema): ?JsonResponse
    {
        if (!$this->request->validateStringPosts($post_string_schema)) {
            return Response::badRequest($this->request->error);
        }
        return null;
    }

    /**
     * Safe validation method for use with Swoole
     * Returns the error response if validation fails
     * 
     * @param array<string> $post_schema Validation schema
     * @return JsonResponse|null Error response or null if validation passes
     */
    protected function safeValidatePosts(array $post_schema): ?JsonResponse
    {
        // Use our non-die version
        return $this->validatePosts($post_schema);
    }

    /**
     * Safe string validation method for use with Swoole
     * Returns the error response if validation fails
     * 
     * @param array<string> $post_string_schema Validation schema
     * @return JsonResponse|null Error response or null if validation passes
     */
    protected function safeValidateStringPosts(array $post_string_schema): ?JsonResponse
    {
        // Use our non-die version
        return $this->validateStringPosts($post_string_schema);
    }

    /**
     * Generates mock response data for API documentation
     * 
     * @param string $method Method name
     * @return array<string, mixed> Mock response data
     */
    public static function mockResponse(string $method): array
    {
        return [];
    }
} 