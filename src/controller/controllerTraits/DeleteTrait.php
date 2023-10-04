<?php
namespace Gemvc\ControllerTraits;

trait DeleteTrait
{
    public function delete(): void
    {
        if ($this->PayloadHasId()) {
            $model = new $this->model($this->payload->id);
            if (!isset($model->id) || $model->id < 1) {
                $this->response->notFound('no object found with given id: ' . $this->payload->id);
            } else {
                if ($model->safeDelete()) {
                    $model = new $this->model($this->payload->id);
                    $this->response->deleted($model, 1, 'deleted');
                } else {
                    $this->response->internalError($model->getError());
                }
            }
        }
    }
}

