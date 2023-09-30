<?php
namespace Gemvc\ControllerTraits;

trait UpdateTrait
{
    public function update(): void
    {

        if ($this->PayloadHasId()) {
            $model = new $this->model($this->payload->id);
            if (!isset($model->id) || !$model->id) {
                $this->response->notFound('no object found with given id: ' . $this->payload->id);
            } else {
                $payloadProps = get_object_vars($this->payload);
                foreach ($payloadProps as $property => $value) {
                    if (property_exists($model, $property) && $property !== "id") {
                        $model->$property = $value;
                    }
                }
                if ($model->update()) {
                    $model = new $this->model($this->payload->id);
                    $this->response->updated($model, 1);
                } else {
                    $this->response->internalError($model->getError());
                }
            }
        }
    }
}

