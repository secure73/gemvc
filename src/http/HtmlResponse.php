<?php

namespace Gemvc\Http;

class HtmlResponse implements ResponseInterface
{
    private string $content;
    private int $status;
    /** @var array<string, string> */
    private array $headers;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(string $content, int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = array_merge(['Content-Type' => 'text/html'], $headers);
    }

    /**
     * Show the response in Swoole
     * 
     * @param \OpenSwoole\HTTP\Response $response The Swoole response object
     */
    public function showSwoole($response): void
    {
        foreach ($this->headers as $key => $value) {
            $response->header($key, $value);
        }
        $response->status($this->status);
        $response->end($this->content);
    }

    /**
     * Show the response in Apache/Nginx (standard PHP)
     */
    public function show(): void
    {
        // Set status code
        http_response_code($this->status);
        
        // Set headers
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
        
        // Output content
        echo $this->content;
    }

    /**
     * @param array<string, string> $headers
     */
    public static function create(string $content, int $status = 200, array $headers = []): self
    {
        return new self($content, $status, $headers);
    }
} 