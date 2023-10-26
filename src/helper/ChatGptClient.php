<?php

namespace GemLibrary\Helper;

require 'vendor/autoload.php';

use GuzzleHttp;
use GuzzleHttp\Exception\RequestException;

class ChatGptClient
{

    private GuzzleHttp\Client  $client;
    private string $baseURL = 'https://api.openai.com/v1/';
    private string $apiKey = '';

    public function __construct(string $apiKey = null)
    {
        $this->apiKey = $apiKey ?: $this->apiKey;
        $this->client = new GuzzleHttp\Client([
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    /**
     * @param string $endpoint 'chat/completion'
     * @param string $sysMessage
     * @param string $userQuestion
     */
    public function sendRequest(string $endpoint, string $sysMessage , string $userQuestion): string
    {
        $data = [
            'messages' => [
                ['role' => 'system', 'content' => trim($sysMessage)],
                ['role' => 'user', 'content' => trim($userQuestion)],
            ]
        ];
        $data = json_encode($data);
        try {
            $response = $this->client->request('POST', $this->baseURL . $endpoint, [
                'body' => $data
            ]);
            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            // Handle request exception
            return $e->getMessage();
        }
    }
}
