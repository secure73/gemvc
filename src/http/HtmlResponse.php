<?php

namespace Gemvc\Http;

class HtmlResponse
{
    private string $content;
    private int $status;
    private array $headers;

    public function __construct(string $content, int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = array_merge(['Content-Type' => 'text/html'], $headers);
    }

    /**
     * Show the response in Swoole
     * 
     * @param \Swoole\Http\Response $response The Swoole response object
     */
    public function showSwoole($response): void
    {
        foreach ($this->headers as $key => $value) {
            $response->header($key, $value);
        }
        $response->status($this->status);
        $response->end($this->content);
    }

    public static function create(string $content, int $status = 200, array $headers = []): self
    {
        return new self($content, $status, $headers);
    }
} 