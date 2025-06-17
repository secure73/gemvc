<?php

namespace App\Controller;

use App\Model\UserModel;
use Gemvc\Core\Controller;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;
use Gemvc\Http\Response;

class UserController extends Controller
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * Create new User
     * 
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {   
        //'password'=>'setPassword()' call the setPassword() method in the UserModel class
        $model = $this->request->mapPostToObject(new UserModel(),['email'=>'email','name'=>'name','description'=>'description','password'=>'setPassword()']);
        if(!$model instanceof UserModel) {
            return $this->request->returnResponse();
        }
        return $model->createModel();
    }

    /**
     * Get User by ID
     * 
     * @return JsonResponse
     */
    public function read(): JsonResponse
    {
        $model = $this->request->mapPostToObject(new UserModel());
        if(!$model instanceof UserModel) {
            return $this->request->returnResponse();
        }
        return $model->readModel();
    }

    /**
     * Update existing User
     * 
     * @return JsonResponse
     */
    public function update(): JsonResponse
    {
        $model = $this->request->mapPostToObject(new UserModel());
        if(!$model instanceof UserModel) {
            return $this->request->returnResponse();
        }
        return $model->updateModel();
    }

    /**
     * Delete User
     * 
     * @return JsonResponse
     */
    public function delete(): JsonResponse
    {
        $model = $this->request->mapPostToObject(new UserModel());
        if(!$model) {
            return $this->request->returnResponse();
        }
        return $model->deleteModel();
    }

    /**
     * Get list of Users with filtering and sorting
     * 
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $model = new UserModel();
        return $this->createList($model);
    }
} 