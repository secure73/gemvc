<?php
namespace Gemvc\Traits\Model;
use Gemvc\Http\Response;
use Gemvc\Http\JsonResponse;
/**
 * Summary of UpdateTrait
 * @method updateJsonResponse()
 */
trait UpdateTrait
{

    public function updateJsonResponse():JsonResponse
    {
        $result = $this->update('id', $this->id);
        if($result === null)
        {
            return Response::internalError("error in update Query: ".$this->getTable().": ".$this->getError());
        }
        return Response::updated($result,1,"updated successfully");
    }
}
