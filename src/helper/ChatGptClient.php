<?php

namespace GemLibrary\Helper;


use GemLibrary\Http\JsonResponse;
use GemLibrary\Http\ApiCall;
class ChatGptClient
{
    private string $baseURL = 'https://api.openai.com/v1/';
    private string $apiKey = '';
    private ApiCall $apiCall;

    public function __construct(string $apiKey = null)
    {
        $this->apiKey = $apiKey ?: $this->apiKey;
        $this->apiCall = new ApiCall();
        $this->apiCall->authorizationHeader = 'Bearer ' . $this->apiKey;
    }

    /**
     * @param string $endpoint     'chat/completion'
     * @param string $sysMessage
     * @param string $userQuestion
     */
    public function sendRequest(string $endpoint, string $sysMessage , string $userQuestion): JsonResponse
    {
        $response = new JsonResponse();
        $data = [
            'messages' => [
                ['role' => 'system', 'content' => trim($sysMessage)],
                ['role' => 'user', 'content' => trim($userQuestion)],
            ]
        ];
        $data = json_encode($data);
        $this->apiCall->post['body'] = $data;

        $result = $this->apiCall->call($this->baseURL.$endpoint);
        if($result) {
            $result = json_decode($result);
            return $response->success($result);
        }
        return $response->badRequest('chat gpt did not answer');
    }
}
