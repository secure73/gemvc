<?php
namespace Gemvc\Http;

/**
 * Create request and send it to remote API.
 *
 * Backward-compatible enhancements:
 * - Optional timeouts: setTimeouts($connectTimeout, $timeout)
 * - Optional SSL client cert/key: setSsl($cert, $key, $ca = null, $verifyPeer = true, $verifyHost = 2)
 * - Optional retries with backoff: setRetries($maxRetries, $retryDelayMs = 200, $retryOnHttpCodes = [429, 500, 502, 503, 504])
 * - Optional network retry toggle: retryOnNetworkError(true|false)
 * - New helpers for flexible bodies (opt-in): postForm(), postMultipart(), postRaw()
 *
 * Defaults preserve legacy behavior:
 * - No custom timeouts/retries unless explicitly set
 * - No SSL client options unless explicitly set
 * - Legacy header behavior is preserved (including overwrite logic in setAuthorization)
 */
class ApiCall
{
    /**
     * Last cURL error message (empty string if none).
     * Defaults to 'call not initialized' until call() runs.
     */
    public ?string $error;

    /**
     * HTTP response code from last request (0 if not executed).
     */
    public int $http_response_code;

    /**
     * User headers as an associative array: ['Header-Name' => 'value']
     * Legacy name and type kept for backward compatibility.
     *
     * @var array<string>
     */
    public array $header;

    /**
     * HTTP method. One of GET, POST, PUT, or custom.
     */
    public string $method;

    /**
     * User payload for legacy JSON flow.
     *
     * @var array<mixed>
     */
    public array $data;

    /**
     * Authorization header (legacy behavior):
     * - If string: setAuthorization() will overwrite previous header list
     * - If array|string[]: not used by legacy logic; kept for compatibility
     *
     * @var null|string|array<string>
     */
    public null|string|array $authorizationHeader;

    /**
     * Response body as string on success, or false on failure.
     */
    public bool|string $responseBody;

    /**
     * Files for legacy multipart flow: ['field' => '/path/to/file']
     *
     * @var array<mixed>
     */
    public array $files;

    // ----- New (opt-in) capabilities: preserved defaults keep legacy behavior -----

    /**
     * Connection timeout in seconds (0 keeps legacy behavior).
     */
    private int $connect_timeout = 0;

    /**
     * Total request timeout in seconds (0 keeps legacy behavior).
     */
    private int $timeout = 0;

    /**
     * SSL client certificate path (optional).
     */
    private ?string $ssl_cert = null;

    /**
     * SSL client private key path (optional).
     */
    private ?string $ssl_key = null;

    /**
     * CA certificate path (optional).
     */
    private ?string $ssl_ca = null;

    /**
     * Verify peer flag (true by default).
     */
    private bool $ssl_verify_peer = true;

    /**
     * Verify host setting: 0, 1, or 2 (2 by default).
     */
    private int $ssl_verify_host = 2;

    /**
     * Maximum retry attempts (0 = no retries).
     */
    private int $max_retries = 0;

    /**
     * Delay between retries in milliseconds.
     */
    private int $retry_delay_ms = 200;

    /**
     * HTTP codes that trigger a retry (opt-in).
     *
     * @var array<int>
     */
    private array $retry_on_http_codes = [429, 500, 502, 503, 504];

    /**
     * Retry on network error (cURL error) if true.
     */
    private bool $retry_on_network_error = true;

    /**
     * Raw request body (when using postRaw()).
     */
    private ?string $rawBody = null;

    /**
     * Form fields (application/x-www-form-urlencoded or multipart/form-data).
     *
     * @var array<string,mixed>|null
     */
    private ?array $formFields = null;

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

    // ---------------- New helper APIs (opt-in, non-breaking) ----------------

    /**
     * Configure connection and total timeouts (seconds).
     * Defaults (0) keep legacy behavior.
     */
    public function setTimeouts(int $connectTimeout, int $timeout): self
    {
        $this->connect_timeout = max(0, $connectTimeout);
        $this->timeout = max(0, $timeout);
        return $this;
    }

    /**
     * Configure SSL client options.
     * If not set, legacy behavior remains unchanged.
     */
    public function setSsl(?string $certPath, ?string $keyPath, ?string $caPath = null, bool $verifyPeer = true, int $verifyHost = 2): self
    {
        $this->ssl_cert = $certPath;
        $this->ssl_key = $keyPath;
        $this->ssl_ca = $caPath;
        $this->ssl_verify_peer = $verifyPeer;
        $this->ssl_verify_host = $verifyHost;
        return $this;
    }

    /**
     * Configure retry behavior (opt-in).
     *
     * @param array<int> $retryOnHttpCodes
     */
    public function setRetries(int $maxRetries, int $retryDelayMs = 200, array $retryOnHttpCodes = []): self
    {
        $this->max_retries = max(0, $maxRetries);
        $this->retry_delay_ms = max(0, $retryDelayMs);
        if (!empty($retryOnHttpCodes)) {
            $this->retry_on_http_codes = array_values(array_unique(array_map('intval', $retryOnHttpCodes)));
        }
        return $this;
    }

    /**
     * Enable/disable retry on network (cURL) errors.
     */
    public function retryOnNetworkError(bool $retry): self
    {
        $this->retry_on_network_error = $retry;
        return $this;
    }

    /**
     * POST with application/x-www-form-urlencoded body (opt-in).
     */
    public function postForm(string $remoteApiUrl, array $fields = []): string|false
    {
        $this->method = 'POST';
        $this->formFields = $fields;
        $this->rawBody = null;
        return $this->call($remoteApiUrl);
    }

    /**
     * POST multipart/form-data with files (opt-in).
     *
     * @param array<string,string> $files Map of field => filePath
     */
    public function postMultipart(string $remoteApiUrl, array $fields = [], array $files = []): string|false
    {
        $this->method = 'POST';
        $this->formFields = $fields;
        $this->files = $files;
        $this->rawBody = null;
        return $this->call($remoteApiUrl);
    }

    /**
     * POST with raw body and explicit content type (opt-in).
     */
    public function postRaw(string $remoteApiUrl, string $rawBody, string $contentType): string|false
    {
        $this->method = 'POST';
        $this->rawBody = $rawBody;
        $this->formFields = null;
        $this->header['Content-Type'] = $contentType;
        return $this->call($remoteApiUrl);
    }

    // ---------------- Existing public API (unchanged behavior) ----------------

    /**
     * Perform a GET request.
     *
     * @param string $remoteApiUrl
     * @param array<string> $queryParams
     */
    public function get(string $remoteApiUrl, array $queryParams = []): string|false
    {
        $this->method = 'GET';
        $this->data = $queryParams;
        $this->rawBody = null;
        $this->formFields = null;

        if (!empty($queryParams)) {
            $remoteApiUrl .= '?' . http_build_query($queryParams);
        }

        return $this->call($remoteApiUrl);
    }

    /**
     * Perform a POST request (legacy JSON behavior preserved).
     *
     * @param string $remoteApiUrl
     * @param array<mixed> $postData
     */
    public function post(string $remoteApiUrl, array $postData = []): string|false
    {
        $this->method = 'POST';
        $this->data = $postData;
        $this->rawBody = null;
        $this->formFields = null;
        return $this->call($remoteApiUrl);
    }

    /**
     * Perform a PUT request (legacy JSON behavior preserved).
     *
     * @param string $remoteApiUrl
     * @param array<mixed> $putData
     */
    public function put(string $remoteApiUrl, array $putData = []): string|false
    {
        $this->method = 'PUT';
        $this->data = $putData;
        $this->rawBody = null;
        $this->formFields = null;
        return $this->call($remoteApiUrl);
    }

    // ---------------- Core call logic (enhanced; defaults preserve legacy) ----------------

    /**
     * Perform the API call.
     * Applies optional timeouts/SSL/retries if configured; otherwise preserves legacy behavior.
     */
    private function call(string $remoteApiUrl): string|false
    {
        // Reset per call
        $this->responseBody = false;
        $this->http_response_code = 0;
        $this->error = '';

        $attempts = $this->max_retries + 1;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $ch = curl_init($remoteApiUrl);
            if ($ch === false) {
                $this->http_response_code = 500;
                $this->error = "remote api $remoteApiUrl is not responding";
                return false;
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'gemserver');

            // Optional timeouts (0 keeps legacy)
            if ($this->connect_timeout > 0) {
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
            }
            if ($this->timeout > 0) {
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            }

            // Optional SSL client/cert configuration
            if ($this->ssl_cert) {
                curl_setopt($ch, CURLOPT_SSLCERT, $this->ssl_cert);
            }
            if ($this->ssl_key) {
                curl_setopt($ch, CURLOPT_SSLKEY, $this->ssl_key);
            }
            if ($this->ssl_ca) {
                curl_setopt($ch, CURLOPT_CAINFO, $this->ssl_ca);
            }
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->ssl_verify_peer ? 1 : 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->ssl_verify_host);

            $this->setMethod($ch);
            $this->setHeaders($ch);
            $this->setAuthorization($ch);
            $this->setData($ch);
            $this->setFiles($ch);

            $this->responseBody = curl_exec($ch);
            $this->http_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->error = curl_error($ch);

            curl_close($ch);

            // Retry policy (opt-in)
            $shouldRetry =
                ($this->retry_on_network_error && is_string($this->error) && $this->error !== '') ||
                in_array($this->http_response_code, $this->retry_on_http_codes, true);

            if ($shouldRetry && $attempt < $attempts) {
                usleep($this->retry_delay_ms * 1000);
                continue;
            }

            if (!is_string($this->responseBody)) {
                return false;
            }
            return $this->responseBody;
        }

        return false;
    }

    /**
     * Set the HTTP method for the request (legacy behavior preserved).
     *
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
     * Set the headers for the request (legacy default JSON header preserved).
     *
     * @param \CurlHandle $ch
     */
    private function setHeaders(\CurlHandle $ch): void
    {
        // Legacy default Content-Type
        $headers = ['Content-Type: application/json'];
        foreach ($this->header as $key => $value) {
            $headers[] = "$key: $value";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Set the authorization header if present (legacy overwrite behavior preserved).
     *
     * @param \CurlHandle $ch
     */
    private function setAuthorization(\CurlHandle $ch): void
    {
        // Preserve legacy overwrite behavior if string is provided
        if (is_string($this->authorizationHeader)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $this->authorizationHeader]);
        }
    }

    /**
     * Set the data for the request.
     * Priority (opt-in first):
     *  - Raw body (postRaw)
     *  - Form/multipart (postForm/postMultipart)
     *  - Legacy JSON using $this->data (for POST/PUT)
     *
     * @param \CurlHandle $ch
     * @throws \Exception when JSON encoding fails in legacy flow
     */
    private function setData(\CurlHandle $ch): void
    {
        // Raw body path (opt-in)
        if ($this->rawBody !== null && ($this->method === 'POST' || $this->method === 'PUT' || $this->method === 'PATCH' || $this->method === 'DELETE')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->rawBody);
            return;
        }

        // Form or multipart (opt-in)
        if (($this->method === 'POST' || $this->method === 'PUT') && ($this->formFields !== null || !empty($this->files))) {
            $postFields = $this->formFields ?? [];
            if (!empty($this->files)) {
                foreach ($this->files as $key => $value) {
                    if (is_string($value) && is_file($value)) {
                        $postFields[$key] = new \CURLFile($value);
                    }
                }
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            return;
        }

        // Legacy JSON path for POST/PUT
        if ($this->method === 'POST' || $this->method === 'PUT') {
            $data_to_send = json_encode($this->data);
            if (!is_string($data_to_send)) {
                throw new \Exception('process stopped becase data failed to encod to json format');
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_to_send);
        }
    }

    /**
     * Set the files for the request if any (legacy multipart path).
     * Preserved for backward compatibility when only $files is provided.
     *
     * @param \CurlHandle $ch
     */
    private function setFiles(\CurlHandle $ch): bool
    {
        if (!empty($this->files) && ($this->formFields === null)) {
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
