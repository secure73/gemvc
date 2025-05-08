<?php
namespace Gemvc\Traits\Model;
use Gemvc\Http\Request;

/**
 * @method listObjects()
 * @param Request $request
 * @return array<mixed>|false
 */
trait ListObjectTrait
{
    public function listObjects(Request $request):array|false
    {
        if(isset($request->post['page']))
        {
            $this->setPage((int)$request->post['page']);
        }
        if(isset($request->post['orderby']))
        {
            $this->setOrderBy($request->post['orderby']);
        }
        if(isset($request->post['find']))
        {
            $this->setFind($request->post['find']);
        }
        if(isset($request->post['between']))
        {
            $this->setBetween($request->post['between']);
        }
        if(isset($request->post['where']))
        {
            $this->setWhere($request->post['where']);
        }
        if(!$this->getError())
        {
            return $this->listQuery();
        }
        $this->error = $this->getError();
        return false;
    }
}
