<?php

namespace Gemvc\Core;

use Gemvc\Core\Security;

class Bootstrap
{
    protected RequestDispatcher $requestDispatcher;
    protected Security  $security;
    protected HttpResponse $httpResponse;
    public function __construct()
    {
        $this->httpResponse = new HttpResponse();
        $this->requestDispatcher = new RequestDispatcher();
        if ($this->dispatchtRequest()) {
            /*
            if ($this->securityCheck()) {
               $this->run();
            }
            */
            $this->securityCheck();
            $this->run();
        }
        $this->httpResponse->show();
    }


    private function dispatchtRequest(): bool
    {
        if (!$this->requestDispatcher->validateRequestSchema()) {
            $this->httpResponse->create($this->requestDispatcher->error_code, $this->requestDispatcher->error);
            return false;
        }
        return true;
    }

    private function securityCheck(): bool
    {
        $this->security = new Security($this->requestDispatcher->serviceName, $this->requestDispatcher->functionName);
        if ($this->security->check()) {
            return true;
        } else {
            /** @phpstan-ignore-next-line */
            $this->httpResponse->create($this->security->error_code,null,null, $this->security->error_message);
            return false;
        }
    }

    private function run():void
    {
        $service = 'App\\Service\\' . $this->requestDispatcher->serviceName;
        try {
            $service = new $service();
            $function = $this->requestDispatcher->functionName;
            $service->request = $this->requestDispatcher;
            /* @phpstan-ignore-next-line */
            $service->security = $this->security;
            /* @phpstan-ignore-next-line */
            $service->user_id = $this->security->user_id;
            if(isset($this->requestDispatcher->payload))
            {
                $service->payload = $this->requestDispatcher->payload;
            }
            $service->$function();
            /* @phpstan-ignore-next-line */
            $service->endExecution();
            /* @phpstan-ignore-next-line */
            $this->httpResponse = $service->response;
        } catch (\Exception $e) {
            $this->httpResponse->badRequest($e->__toString());
        }

        
    }


}
