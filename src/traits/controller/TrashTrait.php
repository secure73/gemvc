<?php
namespace Gemvc\Traits\Controller;
use Gemvc\Http\JsonResponse;

/**
 * @method trash()
 * @return JsonResponse
 */
trait TrashTrait{   
    public function trash():JsonResponse
    {
        if(!$this->request->definePostSchema(['?page' => 'int' , '?orderby' => 'string' , '?find' => 'string' , '?between' => 'string' , '?where' => 'string'])){
            $this->response->badRequest($this->request->error);
            return $this->response;
        }
        return $this->model->trash($this->request);
    }
}