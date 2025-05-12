<?php
namespace Gemvc\Traits\Model;

trait SafeDeleteModelTrait
{
    public function safeDelete(int $id=null):bool
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
