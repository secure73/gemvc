<?php
namespace Gemvc\Traits\Model;

use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;
use Gemvc\Http\Response;

/**
 * @method removeById(int $id)
 * @method removeJsonResponse(int $id)
 * @method removeByPostRequest(Request $request)
 */
trait RemoveTrait
{
    public function removeById(int $id):bool
    {
        $result = $this->deleteByIdQuery($id);

        if(!$result)
        {
           return false;
        }
        return true;
    }

    public function removeJsonResponse(int $id):JsonResponse
    {
        if(!$this->removeById($id))
        {
            return Response::internalError("error in remove query: ".$this->getTable()." , ".$this->getError());
        }
        return Response::deleted($id,1,"removed successfully");
    }

    public function removeByPostRequest(Request $request):JsonResponse
    {
        if(!$request->definePostSchema(['id' => 'int']))
        {
            return Response::badRequest($request->error);
        }
        if( !$this->removeById($request->post["id"]))
        {
           return  Response::internalError("error in remove query: ".$this->getTable()." , ".$this->getError());
        }
        return Response::deleted($request->post["id"],1,"removed successfully");
    }
}
