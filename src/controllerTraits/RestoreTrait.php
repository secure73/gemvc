<?php
namespace Gemvc\ControllerTraits;

trait RestoreTrait
{
    public function restore(): void
    {
        if ($this->PayloadHasId()) {
            $model = new $this->model($this->payload->id);
            if (!isset($model->id) || $model->id < 1) {
                $this->response->notFound('no object found with given id: ' . $this->payload->id);
            } else {
                if ($model->restore()) {
                    $model = new $this->model($this->payload->id);
                    $this->response->success($model, 1, 'restored');
                } else {
                    $this->response->internalError($model->getError());
                }
            }
        }
    }
}

