<?php
namespace GemLibrary\Helper;

class ApiCall
{
    public ?string $error;
    public ?int $http_response_code;
    /**
     * @var array<mixed> $post
     */
    public array  $post;
    /**
     * @var null|string|array<string> $authorizationHeader
     */
    public null|string|array $authorizationHeader;
    /**
     * @var array<mixed> $files
     */
    public array $files;
    public function __construct()
    {
        $this->error = 'call not initialized';
        $this->http_response_code = 0;
        $this->post = [];
        $this->authorizationHeader = null;
        $this->files = [];
        
    }

    /**
     * @return object of type CurlHandle
     */
    public function call(string $remoteApiUrl): object|false
    {
        $ch = curl_init($remoteApiUrl);
        if ($ch === false) {
            $this->http_response_code = 500;
            $this->error = "remote api $remoteApiUrl is not responding";
            return false;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        if (is_string($this->authorizationHeader)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $this->authorizationHeader[0]]);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, 'gemserver');

        if (isset($this->files)) {

            foreach ($this->files as $key => $value) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $value);
            }
        }
        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response || !is_string($response)) {
            $this->error = 'remote api is not responding';
            $this->http_response_code = 500;
            return false;
        }
        $object = json_decode($response);
        $this->http_response_code = $object->http_response_code;
        return $object;
    }
}