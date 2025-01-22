<?php
namespace Gemvc\Http;

/**
 * Create request and send it to remote API
 */
class ApiCall
{
    public ?string $error;
    public int $http_response_code;

    /**
     * Summary of header
     * @var array<string>
     */
    public array $header;
    public string $method;
    /**
     * @var array<mixed> $data
     */
    public array $data;
    /**
     * @var null|string|array<string> $authorizationHeader
     */
    public null|string|array $authorizationHeader;
    public bool|string $responseBody;
    /**
     * @var array<mixed> $files
     */
    public array $files;

    public function __construct()
    {
        $this->error = 'call not initialized';
        $this->http_response_code = 0;
        $this->data = [];
        $this->authorizationHeader = null;
        $this->header = [];
        $this->files = [];
        $this->responseBody = false;
        $this->method = 'GET';
    }

    /**
     * Perform a GET request
     * 
     * @param string $remoteApiUrl
     * @param array<string> $queryParams
     * @return string|false
     */
    public function get(string $remoteApiUrl, array $queryParams = []): string|false
    {
        $this->method = 'GET';
        $this->data = $queryParams;

        if (!empty($queryParams)) {
            $remoteApiUrl .= '?' . http_build_query($queryParams);
        }

        return $this->call($remoteApiUrl);
    }

    /**
     * Perform a POST request
     * 
     * @param string $remoteApiUrl
     * @param array<mixed> $postData
     * @return string|false
     */
    public function post(string $remoteApiUrl, array $postData = []): string|false
    {
        $this->method = 'POST';
        $this->data = $postData;
        return $this->call($remoteApiUrl);
    }

    /**
     * Perform a PUT request
     * 
     * @param string $remoteApiUrl
     * @param array<mixed> $putData
     * @return string|false
     */
    public function put(string $remoteApiUrl, array $putData = []): string|false
    {
        $this->method = 'PUT';
        $this->data = $putData;
        return $this->call($remoteApiUrl);
    }
    /**
     * Perform the API call
     * 
     * @param string $remoteApiUrl
     * @return string|false
     */
    private function call(string $remoteApiUrl): string|false
    {
        $ch = curl_init($remoteApiUrl);
        if ($ch === false) {
            $this->http_response_code = 500;
            $this->error = "remote api $remoteApiUrl is not responding";
            return false;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'gemserver');

        $this->setMethod($ch);
        $this->setHeaders($ch);
        $this->setAuthorization($ch);
        $this->setData($ch);
        $this->setFiles($ch);

        $this->responseBody = curl_exec($ch);
        $this->http_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->error = curl_error($ch);

        curl_close($ch);

        if (!is_string($this->responseBody)) {
            return false;
        }
        return $this->responseBody;
    }

    /**
     * Set the HTTP method for the request
     * @param \CurlHandle $ch
     */
    private function setMethod($ch): void
    {
        if ($this->method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } elseif ($this->method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        } elseif ($this->method !== 'GET' && $this->method !== '') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
        }
    }


    /**
     * Set the headers for the request
     * @param \CurlHandle $ch 
     */
    private function setHeaders(\CurlHandle $ch): void
    {
        $headers = ['Content-Type: application/json'];
        foreach ($this->header as $key => $value) {
            $headers[] = "$key: $value";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Set the authorization header if present
     */
    private function setAuthorization(\CurlHandle $ch): void
    {
        if (is_string($this->authorizationHeader)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $this->authorizationHeader]);
        }
    }

    /**
     * Set the data for the request
     */
    private function setData(\CurlHandle $ch): void
    {
        if ($this->method === 'POST' || $this->method === 'PUT') {
            $data_to_send = json_encode($this->data);

            if (!is_string($data_to_send)) {
                throw new \Exception('process stopped becase data failed to encod to json format');
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_to_send);
        }
    }

    /**
     * Set the files for the request if any
     */
    private function setFiles(\CurlHandle $ch): bool
    {
        if (!empty($this->files)) {
            $postFields = $this->data;
            foreach ($this->files as $key => $value) {
                if (is_string($value)) {
                    $postFields[$key] = new \CURLFile($value);
                    $step_one = curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                    $step_two = curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data']);
                    if ($step_one && $step_two) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
