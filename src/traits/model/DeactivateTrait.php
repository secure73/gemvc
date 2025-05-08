<?php
namespace Gemvc\Traits\Model;

trait DeactivateTrait
{
    public function deactivate(int $id = null):bool
    {
        if($id)
        {
            $this->id = $id;
        }
        
        if(!$this->deactivateQuery())
        {
            return false;
        }
        return true;
    }
}