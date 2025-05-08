<?php
namespace Gemvc\Traits\Controller;

use Gemvc\Http\JsonResponse;

/**
 * @method activate()
 * @return JsonResponse
 */
trait ActivateTrait
{
    public function activate(): JsonResponse
    {
        if(!$this->request->definePostSchema(['id' => 'int'])){
            $this->response->badRequest($this->request->error);
            return $this->response;
        }
        return $this->model->activate($this->request);
    }
}