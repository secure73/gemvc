<?php
namespace Gemvc\ControllerTraits;
use Gemvc\Helper\TypeHelper;
trait CreateTrait
{
    public function create(): void
    {
        if (isset($this->payload)) {
            $model = new $this->model();
            $payloadProps = get_object_vars($this->payload);
            foreach ($payloadProps as $property => $value) {
                if (property_exists($model, $property)) {

                    $model->$property = $value;
                }
            }
            $mustSet = TypeHelper::getNonNullableProperties($model);
            $not_set = array();
            foreach ($mustSet as $item) {
                if (!isset($model->$item)) {
                    $not_set[] = $item;
                }
            }
            if (count($not_set) == 0) {

                $result = $model->insert();
                if ($result) {
                    $model = new $this->model($result);
                    $this->response->success($model);
                } else {
                    $this->response->internalError($model->getError());
                }
            } else {
                $this->response->payloadNeedItems($not_set);
            }
        } else {
            $this->response->badRequest('payload not found');
        }
    }
}

