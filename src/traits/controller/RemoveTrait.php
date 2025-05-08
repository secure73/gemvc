<?php
namespace Gemvc\Traits\Controller;

use Gemvc\Http\JsonResponse;

/**
 * @method remove()
 * @return JsonResponse
 */
trait RemoveTrait
{
    public function remove(): JsonResponse
    {
        if(!$this->request->definePostSchema(['id' => 'int'])){
            $this->response->badRequest($this->request->error);
            return $this->response;
        }
        return $this->model->remove($this->request);
    }
}