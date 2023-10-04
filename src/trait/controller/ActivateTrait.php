<?php
namespace Gemvc\Trait\Controller;
trait ActivateTrait
{
    public function activate(): void
    {
        if ($this->PayloadHasId()) {
            $model = new $this->model($this->payload->id);
            if (!isset($model->id) || $model->id < 1) {
                $this->response->notFound('no object found with given id: ' . $this->payload->id);
            } else {
                if ($model->activate()) {
                    $model = new $this->model($this->payload->id);
                    $this->response->success($model, 1, 'activated');
                } else {
                    $this->response->internalError($model->getError());
                }
            }
        }
    }
}

