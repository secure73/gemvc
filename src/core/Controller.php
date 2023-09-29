<?php

namespace Gemvc\Core;

use Gemvc\Helper\TypeHelper;

class Controller
{
    public HttpResponse      $response;
    public RequestDispatcher $request;
    public ?object           $payload;
    public ?int              $user_id;
    public float             $cost;
    protected ?object        $model; 
    protected string     $requestTime;
    private   float      $start_execution_time;
    

    public function __construct()
    {
        
        $this->response = new HttpResponse();
        $this->requestTime = TypeHelper::timeStamp();
        $this->start_execution_time = microtime(true);
    }

    /**
     * @param array<string> $arrayNeededPayloadKeys
     */
    protected function neededPayloadKeys(array $arrayNeededPayloadKeys): false|object
    {
        if(isset($this->payload))
        {
            $notfound = array();
            foreach($arrayNeededPayloadKeys as $item)
            {
                if(!isset($this->payload->$item))
                {
                    $notfound[] = $item;
                }
            }
            if(count($notfound)>0)
            {
                $message = "";
                foreach($notfound as $item)
                {
                    $message .= $item;
                }
                $this->response->badRequest('in Payload not found:'.$message);
            }
            else
            {
                return $this->payload;
            }

        }
        return false;

    }

    public function endExecution(): void
    {
        $now = microtime(true);
        $this->cost = ($now - $this->start_execution_time) * 1000;
        $this->response->cost = $this->cost;
    }
}
