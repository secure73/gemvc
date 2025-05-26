<?php
namespace App\Api;


use Gemvc\Core\ApiService;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;
use Gemvc\Http\Response;
use Gemvc\Core\Documentation;
use Gemvc\Http\HtmlResponse;
use Gemvc\Core\RedisManager;
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
     * @return HtmlResponse
     * @http GET
     * @hidden
     */
    public function document(): HtmlResponse
    {
        //how to call redis
        $redis = RedisManager::getInstance();
        //how to get cache from redis
        $html = $redis->get('API_HTML_DOCUMENTATION');
        if($html){
            return new HtmlResponse($html);
        }
        $doc = new Documentation();
        $generator = new \Gemvc\Core\ApiDocGenerator();
        $documentation = $generator->generate();

        $reflection = new \ReflectionClass($doc);
        $method = $reflection->getMethod('generateHtmlView');
        $method->setAccessible(true);
        $html = $method->invoke($doc, $documentation);
        //set cache for 1 minute 
        $redis->set('API_HTML_DOCUMENTATION', $html,time() + 60);
        return new HtmlResponse($html);
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