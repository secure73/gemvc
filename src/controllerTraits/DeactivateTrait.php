<?php
namespace Gemvc\ControllerTraits;
trait DeactivateTrait
{
    public function deactivate(): void
    {
        if ($this->PayloadHasId()) {
            $model = new $this->model($this->payload->id);
            if (!isset($model->id) || $model->id < 1) {
                $this->response->notFound('no object found with given id: ' . $this->payload->id);
            } else {
                if ($model->deactivate()) {
                    $model = new $this->model($this->payload->id);
                    $this->response->success($model, 1, 'deactivated');
                } else {
                    $this->response->internalError($model->getError());
                }
            }
        }
    }
}

