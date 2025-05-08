<?php
namespace Gemvc\Traits\Controller;
use Gemvc\Http\JsonResponse;

/**
 * @method delete()
 * @return JsonResponse
 */
trait DeleteTrait
{
    public function delete(): JsonResponse
    {
        if(!$this->request->definePostSchema(['id' => 'int'])){
            $this->response->badRequest($this->request->error);
            return $this->response;
        }
        return $this->model->delete($this->request);
    }
}