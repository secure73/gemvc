<?php
namespace App\Api;


use Gemvc\Core\ApiService;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;
use Gemvc\Http\Response;
use Gemvc\Core\Documentation;
class Index extends ApiService
{
    /**
     * Constructor
     * 
     * @param Request $request The HTTP request object
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * Create new Index
     * @return JsonResponse
     * @http GET
     * @description test if Gemvc successfully installed and Swoole Server running
     */
    public function index(): JsonResponse
    {
        return Response::success('server running');
    }

    /**
     * Summary of document this method is special and reserved for documentation
     * @param mixed $swooleResponse
     * @return void
     * @hidden
     */
    public function document($swooleResponse): void
    {
        $doc = new Documentation();
        $generator = new \Gemvc\Core\ApiDocGenerator();
        $documentation = $generator->generate();

        $reflection = new \ReflectionClass($doc);
        $method = $reflection->getMethod('generateHtmlView');
        $method->setAccessible(true);
        $html = $method->invoke($doc, $documentation);

        $swooleResponse->header('Content-Type', 'text/html');
        $swooleResponse->status(200);
        $swooleResponse->end($html);
    }

   
    /**
     * Generates mock responses for API documentation
     * 
     * @param string $method The API method name
     * @return array<mixed> Example response data for the specified method
     * @hidden
     */
    public static function mockResponse(string $method): array
    {
        return match($method) {
            'index' => [
                'response_code' => 200,
                'message' => 'success',
                'count' => 1,
                'service_message' => 'Index created successfully',
                'data' => [
                    'id' => 1,
                    'name' => 'Sample Index',
                    'description' => 'Index description'
                ]
            ],
            default => [
                'response_code' => 200,
                'message' => 'OK',
                'count' => null,
                'service_message' => null,
                'data' => null
            ]
        };
    }
}