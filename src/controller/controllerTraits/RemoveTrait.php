<?php
namespace Gemvc\ControllerTraits;

trait RemoveTrait
{
    public function remove(): void
    {
        if ($this->PayloadHasId()) {
            $model = new $this->model($this->payload->id);
            if (!isset($model->id) || $model->id < 1) {
                $this->response->notFound('no object found with given id: ' . $this->payload->id);
            } else {
                if (property_exists($model, 'deleted_at')) {
                    if (!$model->deleted_at) {
                        $this->response->badRequest('you shall first delete item and then remove it from the database');
                    }
                } else {
                    if ($model->delete()) {
                        $this->response->delete($model, 1, 'removed from Database');
                    } else {
                        $this->response->internalError($model->getError());
                    }
                }
            }
        }
    }
}

