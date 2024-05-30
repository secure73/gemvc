<?php
namespace Gemvc\Http;

/**
 * create request and send it to remote API
 */
class ApiCall
{
    public ?string $error;
    public int $http_response_code;
    /**
     * @var array<mixed> $post
     */
    public array  $post;
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
        $this->post = [];
        $this->authorizationHeader = null;
        $this->files = [];
        $this->responseBody = false;
    }


    public function call(string $remoteApiUrl): string|false
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
        $this->responseBody = curl_exec($ch);
        $this->http_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->error = curl_error($ch);

        curl_close($ch);
        if(!is_string($this->responseBody)) {
            return false;
        }
        return $this->responseBody;
    }
}
