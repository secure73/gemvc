<?php
namespace Gemvc\Trait\Controller;
trait ActivateTrait
{
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
}

