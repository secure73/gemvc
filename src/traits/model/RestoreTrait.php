<?php
namespace Gemvc\Traits\Model;

trait RestoreTrait
{
    public function restore(int $id = null):bool
    {
        if($id)
        {
            $this->id = $id;
        }
        $table = $this->setTable();
        if(!$table)
        {
            $this->setError('table is not setted in function setTable');
            return false;
        }
        if(!$this->id || $this->id < 1)
        {
            $this->setError('property id does existed or not setted in object');
            return false;
        }

        $query = "UPDATE {$this->setTable()} SET deleted_at = NULL WHERE id = :id";
        if(!$this->updateQuery($query, [':id' => $this->id]))
        {
            $this->setError($this->getError());
            return false;
        }
        return true;
    }
}
