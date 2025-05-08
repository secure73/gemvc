<?php
namespace Gemvc\Traits\Model;

use Gemvc\Http\JsonResponse;
use Gemvc\Http\Response;
use Gemvc\Core\Table;

trait ActivateTrait
{
  
    public function activateModel(Table $instanceTable): Table
    {
        if(!isset($this->request->post['id']) || !$this->request->post['id'] || !is_integer($this->request->post['id']))
        {
            Response::unprocessableEntity('post id not found or contains non-numeric value')->show();
            die();
        }
        
        if(!$this->activateQuery($this->request->post['id']))
        {
            Response::internalError($instanceTable->getError())->show();
            die();
        }
        
        return $instanceTable;
    }

    public function activateModelJsonResponse(Table $instanceTable): JsonResponse
    {
        return Response::success($this->activateModel($instanceTable), 1);
    }
    
    public function deactivateModelJsonResponse(Table $instanceTable): JsonResponse
    {
        
        return Response::success($this->deactivateModel($instanceTable), 1);
    }


    public function deactivateModel(Table $instanceTable): Table
    {
        if(!isset($this->request->post['id']) || !$this->request->post['id'] || !is_integer($this->request->post['id']))
        {
            Response::unprocessableEntity('post id not found or contains non-numeric value')->show();
            die();
        }
        
        if(!$this->deactivateQuery($this->request->post['id']))
        {
            Response::internalError($instanceTable->getError())->show();
            die();
        }
        return $instanceTable;
    }
}