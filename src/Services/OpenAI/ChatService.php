<?php
// src/Services/OpenAI/ChatService.php

class ChatService {
    private $apiKey;
    private $model;
    private $maxTokens;
    private $temperature;
    
    public function __construct() {
        // Load configuration
        $config = require __DIR__ . '/../../../config/config.php';
        
        $this->apiKey = $config['api_key'];
        $this->model = $config['model'];
        $this->maxTokens = $config['max_tokens'];
        $this->temperature = $config['temperature'];
        
        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API key not configured');
        }
    }
    
    public function sendMessage($userMessage, $conversationHistory = []) {
        // Prepare messages array for OpenAI
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a helpful AI assistant. Provide clear, helpful, and concise responses.'
            ]
        ];
        
        // Add conversation history (keeping it reasonable length)
        $recentHistory = array_slice($conversationHistory, -10); // Last 10 messages
        $messages = array_merge($messages, $recentHistory);
        
        // Prepare the API request
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'stream' => false
        ];
        
        // Make the API call
        $response = $this->callOpenAIAPI($payload);
        
        // Extract and return the response
        if (isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        } else {
            throw new Exception('Invalid response from OpenAI API');
        }
    }
    
    private function callOpenAIAPI($payload) {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Handle curl errors
        if ($response === false) {
            throw new Exception('cURL error: ' . $curlError);
        }
        
        // Parse JSON response
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON parse error: ' . json_last_error_msg());
        }
        
        // Handle HTTP errors
        if ($httpCode !== 200) {
            $errorMessage = isset($decoded['error']['message']) 
                ? $decoded['error']['message'] 
                : "HTTP error: $httpCode";
            throw new Exception("OpenAI API error: " . $errorMessage);
        }
        
        return $decoded;
    }
    
    public function createChatCompletion($messages, $options = []) {
        $payload = array_merge([
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
        ], $options);
        
        return $this->callOpenAIAPI($payload);
    }
}