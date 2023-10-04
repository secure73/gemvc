<?php
namespace Gemvc\Trait\Controller;

trait GetByIdTrait
{
    public function ids(): void
    {
        $model = new $this->model();
        if (isset($this->payload->ids) && is_array($this->payload->ids)) {
            $result = $model->ids($this->payload->ids);
            $this->response->success($result);
        } else {
            $this->response->badRequest('payload need ids ex: {ids:[1,3,5,...]}');
        }
    }
}

