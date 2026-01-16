<?php

namespace App\Services;

use App\Config\AppConfig;
use App\Exceptions\ValidationException;
use Exception;

/**
 * OpenAI Service
 * Handles all interactions with OpenAI API
 * Provides chat completion and structured data extraction
 */
class OpenAIService
{
    private string $apiBaseUrl;
    private ?string $apiKey;
    private string $model;
    private float $temperature;
    private int $maxTokens;
    private int $timeout;
    private int $maxRetries;
    private HttpClient $httpClient;
    private AppConfig $config;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->config = AppConfig::getInstance();
        
        // Load configuration from AppConfig
        $openAIConfig = $this->config->getOpenAIConfig();
        
        $this->apiBaseUrl = $openAIConfig['api_base_url'];
        $this->apiKey = !empty($openAIConfig['api_key']) ? $openAIConfig['api_key'] : null;
        $this->model = $openAIConfig['model'];
        $this->temperature = $openAIConfig['temperature'];
        $this->maxTokens = $openAIConfig['max_tokens'];
        $this->timeout = $openAIConfig['timeout'];
        $this->maxRetries = $openAIConfig['max_retries'];
    }

    /**
     * Check if API key is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Send chat completion request to OpenAI
     * 
     * @param array $messages Array of message objects [['role' => 'user', 'content' => '...']]
     * @param array $options Optional parameters (model, temperature, max_tokens, etc.)
     * @return array API response
     * @throws Exception If API call fails
     */
    public function chat(array $messages, array $options = []): array
    {
        if (!$this->isConfigured()) {
            throw new Exception('OpenAI API key not configured. Please set OPENAI_API_KEY in .env file.');
        }

        // Validate messages format
        if (empty($messages)) {
            throw new ValidationException('Messages array cannot be empty');
        }

        foreach ($messages as $message) {
            if (!isset($message['role']) || !isset($message['content'])) {
                throw new ValidationException('Each message must have "role" and "content" fields');
            }
        }

        // Build request payload
        $payload = [
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? $this->temperature,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
        ];

        // Add JSON mode if requested
        if (isset($options['response_format']) && $options['response_format'] === 'json') {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        // Make API request with retries
        $response = $this->makeRequestWithRetry('/chat/completions', $payload);

        return $response;
    }

    /**
     * Extract structured data using JSON mode
     * Ensures consistent structured output from AI
     * 
     * @param string $systemPrompt System instructions
     * @param string $userPrompt User input/data to analyze
     * @param array $options Additional options
     * @return array Parsed JSON response
     * @throws Exception If extraction fails
     */
    public function extractStructuredData(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => $userPrompt
            ]
        ];

        // Force JSON mode
        $options['response_format'] = 'json';

        $response = $this->chat($messages, $options);

        // Extract and parse JSON from response
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response: missing content');
        }

        $content = $response['choices'][0]['message']['content'];
        $jsonData = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("OpenAI JSON parsing error: " . json_last_error_msg());
            error_log("Raw content: " . $content);
            throw new Exception('Failed to parse AI response as JSON: ' . json_last_error_msg());
        }

        return [
            'data' => $jsonData,
            'raw_response' => $response,
            'usage' => $response['usage'] ?? null
        ];
    }

    /**
     * Make HTTP request to OpenAI API with retry logic
     * 
     * @param string $endpoint API endpoint (e.g., '/chat/completions')
     * @param array $payload Request body
     * @param int $retryCount Current retry attempt
     * @return array API response
     * @throws Exception If all retries fail
     */
    private function makeRequestWithRetry(string $endpoint, array $payload, int $retryCount = 0): array
    {
        try {
            $url = $this->apiBaseUrl . $endpoint;
            
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ];

            $options = [
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_SSL_VERIFYPEER => true,
            ];

            $ch = curl_init($url);
            curl_setopt_array($ch, $options);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                throw new Exception("CURL error: " . $error);
            }

            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to parse API response: ' . json_last_error_msg());
            }

            // Handle HTTP errors
            if ($httpCode >= 400) {
                $errorMessage = $data['error']['message'] ?? 'Unknown error';
                $errorType = $data['error']['type'] ?? 'api_error';
                
                // Retry on rate limit or server errors
                if (($httpCode === 429 || $httpCode >= 500) && $retryCount < $this->maxRetries) {
                    $waitTime = pow(2, $retryCount); // Exponential backoff: 1s, 2s, 4s
                    error_log("OpenAI API error (HTTP {$httpCode}), retrying in {$waitTime}s... (attempt " . ($retryCount + 1) . "/" . $this->maxRetries . ")");
                    sleep($waitTime);
                    return $this->makeRequestWithRetry($endpoint, $payload, $retryCount + 1);
                }
                
                throw new Exception("OpenAI API error ({$errorType}): {$errorMessage} (HTTP {$httpCode})");
            }

            return $data;

        } catch (Exception $e) {
            // Retry on network errors
            if ($retryCount < $this->maxRetries && strpos($e->getMessage(), 'CURL error') !== false) {
                $waitTime = pow(2, $retryCount);
                error_log("Network error, retrying in {$waitTime}s... (attempt " . ($retryCount + 1) . "/" . $this->maxRetries . ")");
                sleep($waitTime);
                return $this->makeRequestWithRetry($endpoint, $payload, $retryCount + 1);
            }
            
            throw $e;
        }
    }

    /**
     * Get current token usage estimate for text
     * Rough approximation: 1 token ≈ 4 characters
     * 
     * @param string $text Text to estimate
     * @return int Estimated token count
     */
    public function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Get model information
     * 
     * @return array Model configuration
     */
    public function getModelInfo(): array
    {
        return [
            'api_base_url' => $this->apiBaseUrl,
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'timeout' => $this->timeout,
            'max_retries' => $this->maxRetries,
            'configured' => $this->isConfigured()
        ];
    }
}
