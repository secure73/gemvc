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
    protected string         $requestTime;
    private   float          $start_execution_time;
    

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

    private function payloadExists(): bool
    {
        if (!isset($this->payload)) {
            $this->response->badRequest('payload not found');
            return false;
        } else {
            return true;
        }
    }

    private function sqlPage(): string
    {
        if (isset($this->request->page)) {
            $page = $this->request->page;
            return " LIMIT $page , 10 ";
        } else {
            return '';
        }
    }

    private function sqlListOrderBy(object $model): string
    {
        $sqlOrder = '';
        if (isset($this->request->orderby)) {
            foreach ($this->request->orderby as $key => $value) {
                if (property_exists($model, $key)) {
                    if ($value == "asc") {
                        $sqlOrder .= " ORDER BY $key ";
                    } else {

                        $sqlOrder .= " ORDER BY $key DESC ";
                    }
                }
            }
        } else {
            $sqlOrder .= ' ORDER BY id DESC ';
        }
        return $sqlOrder;
    }

    private function PayloadHasId(): bool
    {
        if ($this->payloadExists()) {
            if (isset($this->payload->id)) {
                return true;
            }
        } else {
            $this->response->badRequest('id in payload not found');
        }
        return false;
    }
}
