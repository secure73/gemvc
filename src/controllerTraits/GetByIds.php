<?php
namespace Gemvc\ControllerTraits;

trait GetByIdsTrait
{
    public function id(): void
    {
        if ($this->PayloadHasId()) {
            $this->model = new $this->model($this->payload->id);
            if (isset($this->model->id) && $this->model->id) {
                $this->response->success($this->model);
            } else {
                $this->response->notFound('nothing found with given id: ' . $this->payload->id);
            }
        } else {
            $this->response->payloadNeedItems(['id']);
        }
    }
}

