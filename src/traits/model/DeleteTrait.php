<?php
namespace Gemvc\Traits\Model;

use Gemvc\Traits\Table\UpdateQuery;

trait DeleteTrait
{
    use UpdateQuery;
    public function delete(int $id=null):bool
    {
        if($id)
        {
            $this->id = $id;
        }
        if(!$this->safeDeleteQuery($this->id))
        {
            return false;
        }
        return true;
    }
}
