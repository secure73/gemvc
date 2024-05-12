<?php
namespace GemLibrary\Http;

class Response {

    public static function success(mixed $data ,int $count = null , string $service_message = null):JsonResponse
    {
        return (new JsonResponse())->success($data ,$count,$service_message);
    }
    public function updated(mixed $data ,int $count = null,string $service_message = null):JsonResponse
    {
        $service_message = 'updated: '.$service_message;
        return (new JsonResponse())->updated($data ,$count,$service_message);
    }

    public function created(mixed $data ,int $count = null,string $service_message = null):JsonResponse
    {
        $service_message = 'created: '.$service_message;
        return (new JsonResponse())->created($data ,$count,$service_message);    
    }

    public function successButNoContentToShow(mixed $data ,int $count = null,string $service_message= null):JsonResponse
    {
         $service_message = 'success but no content to show: '.$service_message;
         return (new JsonResponse())->successButNoContentToShow($data ,$count,$service_message);
    }
    
    public function deleted(mixed $data ,int $count = null,string $service_message = null):JsonResponse
    {
        $service_message = 'deleted: '.$service_message;
        return (new JsonResponse())->deleted($data ,$count,$service_message);
    }
    public function unauthorized(string $service_message = null):JsonResponse
    {
        $service_message = 'unauthorized: '.$service_message;
        return (new JsonResponse())->unauthorized($service_message);
    }
    public function forbidden(string $service_message = null):JsonResponse
    {
        $service_message = 'forbidden: '.$service_message;
        return (new JsonResponse())->forbidden($service_message);
    }
    public function notFound(string $service_message = null):JsonResponse
    {
        $service_message = 'notFound: '.$service_message;
        return (new JsonResponse())->notFound($service_message);
    }

    public function internalError(string $service_message = null ):JsonResponse
    {
        $service_message = 'internalError: '.$service_message;
        return (new JsonResponse())->internalError($service_message);
    }

    public function unknownError(string $service_message = null, mixed $data):JsonResponse
    {
        $service_message = 'unknownError: '.$service_message;
        return (new JsonResponse())->unknownError($service_message, $data);
    }

    public function notAcceptable(string $service_message = null):JsonResponse
    {
        $service_message = 'notAcceptable: '.$service_message;
        return (new JsonResponse())->notAcceptable($service_message);
    }

    public function conflict(string $service_message = null):JsonResponse
    {
        $service_message = 'conflict: '.$service_message;
        return (new JsonResponse())->conflict($service_message);
    }

    public function unsupportedMediaType(string $service_message = null):JsonResponse
    {
        $service_message = 'unsupported MediaType: '.$service_message;
        return (new JsonResponse())->unsupportedMediaType($service_message);
    }

    public function unprocessableEntity(string $service_message = null):JsonResponse
    {
        $service_message = 'Unprocessable Entity: '.$service_message;
        return (new JsonResponse())->unprocessableEntity($service_message);
    }
    
    public function badRequest(string $service_message = null):JsonResponse
    {
        $service_message = 'bad Request: '.$service_message;
        return (new JsonResponse())->badRequest($service_message);
    }
}