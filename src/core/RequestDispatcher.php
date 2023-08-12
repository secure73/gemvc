<?php

namespace Gemvc\Core;
use Gemvc\Helper\TypeHelper;

require_once('app/services.php');

use Gemvc\Helper\JsonHelper;
use stdClass;

class RequestDispatcher
{
    public    string    $serviceName;
    public    string    $functionName;
    public    string    $method;
    public    ?string   $incomming_fileName;
    public    ?string   $incomming_fileExt;
    public    ?string   $incomming_filePath;
    public    ?string   $incomming_fileSize;
    public    ?object   $payload;
    public    ?string   $error = "";
    public    int       $error_code = 0;
    protected string    $incommingRequest;
    public ?object      $find;
    public ?object      $orderby;
    public ?object      $between;
    public ?int         $page;
    public ?int         $count;



    /**
     * @var array<string>
     */
    private array $validFileExtenstions = array('jpeg', 'png', 'jpg', 'gif', 'pdf');

    public function __construct()
    {
        if (isset($_FILES['file'])) {
            $this->incomming_fileName = $_FILES['file']['name'];
            if ($this->checkFileExtension()) {
                $this->incomming_filePath = $_FILES['file']['tmp_name'];
                $this->incomming_fileSize = $_FILES['file']['size'];
            }
        }
    }

    public function getIncommingRequest():string
    {
        return $this->incommingRequest;
    }

    public function validateRequestSchema(): bool
    {
        if (!$this->error) {
            if (isset($_POST['service'])) {
                $this->setOptionalRequest();
                $this->incommingRequest = trim($_POST['service']);
                $this->retriveServiceFromRequest($this->incommingRequest);
                if (isset($_POST['payload'])) {
                    $this->extractPayload($_POST['payload']);
                }
                isset($_POST['action']) ? $this->setPostMethod($_POST['action']) : $this->method = 'GET';
                return true;
            } else {
                $this->error = 'service not found in request';
            }
        }
        return false;
    }

    public function isPost(): bool
    {
        if ($this->method === 'POST') {
            return true;
        }
        return false;
    }

    public function isGet(): bool
    {
        if ($this->method === 'GET') {
            return true;
        }
        return false;
    }

    public function isPut(): bool
    {
        if ($this->method === 'PUT') {
            return true;
        }
        return false;
    }

    public function isDelete(): bool
    {
        if ($this->method === 'DELETE') {
            return true;
        }
        return false;
    }

    

    private function setOptionalRequest():void
    {
        $this->setBetweeen();
        $this->setCount();
        $this->setFind();
        $this->setOrderBy();
        $this->setPage();
    }


    private function setFind():void
    {
        if(isset($_POST['find']))
        {
            $find = trim($_POST['find']);
            $find = JsonHelper::validateJsonStringReturnObject($find);
            if($find)
            {
                $this->find = $find;
            }
        }
    }

    private function setOrderBy():void
    {
        if(isset($_POST['orderby']))
        {
            $find = trim($_POST['orderby']);
            $find = JsonHelper::validateJsonStringReturnObject($find);
            if($find)
            {
                $this->orderby = $find;
            }
        }
    }

    public function setBetweeen():void
    {
        if(isset($_POST['between']))
        {
            $find = trim($_POST['between']);
            $find = JsonHelper::validateJsonStringReturnObject($find);
            if($find)
            {
                $this->between = $find;
            }
        }
    }

    private function setCount():void
    {
        if(isset($_POST['count']))
        {
            $find = trim($_POST['count']);
            if(is_numeric($find))
            {
                $this->count = intval($find);
            }
        }
    }

    private function setPage():void
    {
        if(isset($_POST['page']))
        {
            $find = trim($_POST['page']);
            if(is_numeric($find))
            {
                $this->page = intval($find);
            }
        }
    }




    private function extractPayload(string $payload): bool
    {
        $payload = trim($payload);
        $payload = JsonHelper::validateJsonStringReturnObject($payload);
        if ($payload) {
            $this->payload = ($payload);
            return true;
        }
        $this->error = 'payload is not json formatted';
        return false;
    }


    private function retriveServiceFromRequest(string $servicName): void
    {
        $serviceAndmethod = explode('/', $servicName);
        $this->serviceName = ucfirst($serviceAndmethod[0]);
        (isset($serviceAndmethod[1])) ? $this->functionName = $serviceAndmethod[1] : $this->functionName = 'index';
        //isset($requsetObject->method) ? $this->setPostMethod($requsetObject->method) : $this->setPostMethod('GET');
        //if (isset($requsetObject->payload)) {
        //   $this->payload = $requsetObject->payload;
        // }
    }

    /*
    private function serviceExists(): bool
    {
        if (array_key_exists($this->serviceName, REGISTERED_SERVICES)) {

            $methods = REGISTERED_SERVICES[$this->serviceName];
            if (in_array($this->functionName, $methods)) {
                return true;
            } else {
                $this->error = 'method is not exists/registered';
            }
        } else {
            $this->error = 'service is not exists/registered';
        }
        $this->error_code = 404;
        return false;
    }
    */

    private function checkFileExtension(): bool
    {
        if ($this->incomming_fileName) {
            $fileExt = strtolower(pathinfo($this->incomming_fileName, PATHINFO_EXTENSION)); // get image extension
            if (in_array($fileExt, $this->validFileExtenstions)) {
                $this->incomming_fileExt = $fileExt;
                return true;
            } else {
                $this->error = 'File extension not allowed ' . $fileExt;
            }
        } else {
            $this->error = 'check file Extenstion : File name is null or empty';
        }
        return false;
    }

    private function setPostMethod(string $postMethodName = ""): void
    {
        $postMethodName = strtoupper(trim($postMethodName));
        switch ($postMethodName) {
            case 'POST':
                $this->method = 'POST';
                break;
            case 'PUT':
                $this->method = 'PUT';
                break;
            case 'DELETE':
                $this->method = 'DELETE';
                break;
            default:
                $this->method = 'GET';
                break;
        }
    }
}
