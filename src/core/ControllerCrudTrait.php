<?php

namespace Gemvc\Core;

use Gemvc\Helper\TypeHelper;

trait ControllerCrudTrait
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


    public function restoreActivate(): void
    {
        if ($this->PayloadHasId()) {
            $model = new $this->model($this->payload->id);
            if (!isset($model->id) || $model->id < 1) {
                $this->response->notFound('no object found with given id: ' . $this->payload->id);
            } else {
                if ($model->restoreActivate()) {
                    $model = new $this->model($this->payload->id);
                    $this->response->success($model, 1, 'restored');
                } else {
                    $this->response->internalError($model->getError());
                }
            }
        }
    }

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

    public function list(): void
    {
        $model = new $this->model();
        $table = $model->getTable();
        $sql = "SELECT * FROM $table WHERE deleted_at IS NULL  ";;
        if (isset($this->request->count)) {
            $sql = "SELECT count(*) as founded FROM $table WHERE deleted_at IS NULL  ";
        }

        $arrayBind = [];
        $sqlEqual = null;
        if (isset($this->request->payload)) {
            foreach ($this->request->payload as $key => $value) {
                if (property_exists($model, $key)) {
                    $eqKey = ':eq' . $key;
                    $arrayBind[$eqKey] = $value;
                    $sqlEqual .= " AND  $key = $eqKey ";
                }
            }
        }
        $sql .= $sqlEqual;

        if (isset($this->request->find)) {
            $sqlfind = '';
            foreach ($this->request->find as $key => $value) {
                if (property_exists($model, $key)) {
                    $eqKey = ':like' . $key;
                    $arrayBind[$eqKey] = '%' . $value . '%';
                    $sqlfind .= " AND  $key LIKE $eqKey ";
                }
            }
            $sql .= $sqlfind;
        }


        $sql .= $this->sqlListOrderBy($model);
        $sql .= $this->sqlPage();
        $res = $model->select($sql, $arrayBind);
        if (is_array($res)) {
            $this->response->success($res);
        } else {
            $this->response->badRequest($model->getError());
        }
    }

    public function trash()
    {
        $model = new $this->model();
        $table = $model->getTable();
        $sql = "SELECT * FROM $table WHERE deleted_at IS NOT NULL  ";
        if (isset($this->request->count)) {
            $sql = "SELECT count(*) as founded FROM $table WHERE deleted_at IS NOT NULL  ";
        }

        $arrayBind = [];
        $sqlEqual = null;
        if (isset($this->request->payload)) {
            foreach ($this->request->payload as $key => $value) {
                if (property_exists($model, $key)) {
                    $eqKey = ':eq' . $key;
                    $arrayBind[$eqKey] = $value;
                    $sqlEqual .= " AND  $key = $eqKey ";
                }
            }
        }
        $sql .= $sqlEqual;

        if (isset($this->request->find)) {
            $sqlfind = '';
            foreach ($this->request->find as $key => $value) {
                if (property_exists($model, $key)) {
                    $eqKey = ':like' . $key;
                    $arrayBind[$eqKey] = '%' . $value . '%';
                    $sqlfind .= " AND  $key LIKE $eqKey ";
                }
            }
            $sql .= $sqlfind;
        }


        $sql .= $this->sqlListOrderBy($model);
        $sql .= $this->sqlPage();
        $res = $model->select($sql, $arrayBind);
        if (is_array($res)) {
            $this->response->success($res);
        } else {
            $this->response->badRequest($model->getError());
        }
    }


    public function actives(): void
    {
        $model = new $this->model();
        $table = $model->getTable();
        $sql = "SELECT * FROM $table WHERE deleted_at IS NULL AND isActive = 1 ";
        if (isset($this->request->count)) {
            $sql = "SELECT count(*) as founded FROM $table WHERE deleted_at IS NULL AND isActive = 1 ";
        }

        $arrayBind = [];
        $sqlEqual = null;
        if (isset($this->request->payload)) {
            foreach ($this->request->payload as $key => $value) {
                if (property_exists($model, $key)) {
                    $eqKey = ':eq' . $key;
                    $arrayBind[$eqKey] = $value;
                    $sqlEqual .= " AND  $key = $eqKey ";
                }
            }
        }
        $sql .= $sqlEqual;

        if (isset($this->request->find)) {
            $sqlfind = '';
            foreach ($this->request->find as $key => $value) {
                if (property_exists($model, $key)) {
                    $eqKey = ':like' . $key;
                    $arrayBind[$eqKey] = '%' . $value . '%';
                    $sqlfind .= " AND  $key LIKE $eqKey ";
                }
            }
            $sql .= $sqlfind;
        }


        $sql .= $this->sqlListOrderBy($model);
        $sql .= $this->sqlPage();
        $res = $model->select($sql, $arrayBind);
        if (is_array($res)) {
            $this->response->success($res);
        } else {
            $this->response->badRequest($model->getError());
        }
    }

    public function deactives(): void
    {
        $model = new $this->model();
        $table = $model->getTable();
        $sql = "SELECT * FROM $table WHERE deleted_at IS NULL AND isActive = 0 ";
        if (isset($this->request->count)) {
            $sql = "SELECT count(*) as founded FROM $table WHERE deleted_at IS NULL AND isActive = 0 ";
        }

        $arrayBind = [];
        $sqlEqual = null;
        if (isset($this->request->payload)) {
            foreach ($this->request->payload as $key => $value) {
                if (property_exists($model, $key)) {
                    $eqKey = ':eq' . $key;
                    $arrayBind[$eqKey] = $value;
                    $sqlEqual .= " AND  $key = $eqKey ";
                }
            }
        }
        $sql .= $sqlEqual;

        if (isset($this->request->find)) {
            $sqlfind = '';
            foreach ($this->request->find as $key => $value) {
                if (property_exists($model, $key)) {
                    $eqKey = ':like' . $key;
                    $arrayBind[$eqKey] = '%' . $value . '%';
                    $sqlfind .= " AND  $key LIKE $eqKey ";
                }
            }
            $sql .= $sqlfind;
        }


        $sql .= $this->sqlListOrderBy($model);
        $sql .= $this->sqlPage();
        $res = $model->select($sql, $arrayBind);
        if (is_array($res)) {
            $this->response->success($res);
        } else {
            $this->response->badRequest($model->getError());
        }
    }
    //////////////////////////Private Methods //////////////////////////////////
    private function payloadExists(): bool
    {
        if (!isset($this->payload)) {
            $this->response->badRequest('payload not found');
            return false;
        } else {
            return true;
        }
    }

    private function sqlPage(): string
    {
        if (isset($this->request->page)) {
            $page = $this->request->page;
            return " LIMIT $page , 10 ";
        } else {
            return '';
        }
    }

    private function sqlListOrderBy(object $model): string
    {
        $sqlOrder = '';
        if (isset($this->request->orderby)) {
            foreach ($this->request->orderby as $key => $value) {
                if (property_exists($model, $key)) {
                    if ($value == "asc") {
                        $sqlOrder .= " ORDER BY $key ";
                    } else {

                        $sqlOrder .= " ORDER BY $key DESC ";
                    }
                }
            }
        } else {
            $sqlOrder .= ' ORDER BY id DESC ';
        }
        return $sqlOrder;
    }

    private function PayloadHasId(): bool
    {
        if ($this->payloadExists()) {
            if (isset($this->payload->id)) {
                return true;
            }
        } else {
            $this->response->badRequest('id in payload not found');
        }
        return false;
    }
}
