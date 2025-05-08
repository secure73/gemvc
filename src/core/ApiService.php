<?php

namespace Gemvc\Core;

use Gemvc\Http\Request;
use Gemvc\Http\Response;
use Gemvc\Http\JsonResponse;


/**
 * @function auth(string $role = null):bool
 * @property Request $request
 * public service is suitable for all service without need of Authentication, like Login , Register etc...
 */
class ApiService
{
    protected Request $request;
    public ?string $error;

    public function __construct(Request $request)
    {
        $this->error = null;
        $this->request = $request;
    }

    public function index(): JsonResponse
    {
        $name = get_class($this);
        //because get_class return class name with namespace like App\\Service\\className ,
        //we need only to shoe className and it is in index 2
        $name = explode('\\', $name)[2];
        return Response::success("welcome to $name service");
    }

    /**
     * @param array<string> $post_schema  Define Post Schema to validation
     * @return void
     * validatePosts(['email'=>'email' , 'id'=>'int' , '?name' => 'string'])
     * if not validated , automatic show response and die;
     * @help : ?name means it is optional
     */
    protected function validatePosts(array $post_schema): void
    {
        if (!$this->request->definePostSchema($post_schema)) {
            Response::badRequest($this->request->error)->show();
            die;
        }
    }

    /**
     * Validates string lengths in a dictionary against min and max constraints.
     * if not validated , automatic show response and die;
     * A dictionary where keys are strings and values are strings in the format "key:min-value|max-value" (optional).
     * @param array<string> $post_string_schema an Array where keys are post name and values are strings in the format "key:min-value|max-value" (optional).
     * validateStringPosts([
     *     'username' => '3|15',  // Min length 3, max length 15
     *     'password' => '8|',    // Min length 8, no max limit
     *     'nickname' => '|20',   // No min limit, max length 20
     *     'bio' => '',           // No min or max limit
     * ]);
     */
    protected function validateStringPosts(array $post_string_schema): void
    {
        if (!$this->request->validateStringPosts($post_string_schema)) {
            Response::badRequest($this->request->error)->show();
            die;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function mockResponse(string $method): array
    {
        return [];
    }

}
