<?php

namespace App\Services;

use App\Config\AppConfig;
use App\Exceptions\ValidationException;
use Exception;

/**
 * Gemini Service
 * Handles all interactions with Google Gemini API
 * Provides chat completion and structured data extraction
 */
class GeminiService
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
        $geminiConfig = $this->config->getGeminiConfig();
        
        $this->apiBaseUrl = $geminiConfig['api_base_url'];
        $this->apiKey = !empty($geminiConfig['api_key']) ? $geminiConfig['api_key'] : null;
        $this->model = $geminiConfig['model'];
        $this->temperature = $geminiConfig['temperature'];
        $this->maxTokens = $geminiConfig['max_tokens'];
        $this->timeout = $geminiConfig['timeout'];
        $this->maxRetries = $geminiConfig['max_retries'];
    }

    /**
     * Check if API key is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Send chat completion request to Gemini
     * 
     * @param array $messages Array of message objects [['role' => 'user', 'content' => '...']]
     * @param array $options Optional parameters (model, temperature, max_tokens, etc.)
     * @return array API response
     * @throws Exception If API call fails
     */
    public function chat(array $messages, array $options = []): array
    {
        if (!$this->isConfigured()) {
            throw new Exception('Gemini API key not configured. Please set GEMINI_API_KEY in .env file.');
        }

        // Validate messages format
        if (empty($messages)) {
            throw new ValidationException('Messages array cannot be empty');
        }

        // Convert OpenAI-style messages to Gemini format
        $contents = $this->convertMessagesToGeminiFormat($messages);

        // Build request payload for Gemini
        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? $this->temperature,
                'maxOutputTokens' => $options['max_tokens'] ?? $this->maxTokens,
            ],
        ];

        // Add JSON mode if requested
        if (isset($options['response_format']) && $options['response_format'] === 'json') {
            $payload['generationConfig']['responseMimeType'] = 'application/json';
        }

        // Make API request with retries
        $model = $options['model'] ?? $this->model;
        $response = $this->makeRequestWithRetry($model, $payload);

        return $response;
    }

    /**
     * Extract structured data using JSON mode
     * Ensures consistent structured output from AI
     * 
     * @param string $systemPrompt System instructions
     * @param string $userPrompt User input/data to analyze
     * @param array $options Optional parameters
     * @return array ['success' => bool, 'data' => array, 'raw_response' => string]
     * @throws Exception If API call fails or JSON parsing fails
     */
    public function extractStructuredData(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        // Combine system and user prompts for Gemini
        $combinedPrompt = $systemPrompt . "\n\n" . $userPrompt;

        $messages = [
            ['role' => 'user', 'content' => $combinedPrompt]
        ];

        // Force JSON mode
        $options['response_format'] = 'json';

        try {
            $response = $this->chat($messages, $options);
            
            // Extract text from Gemini response
            $text = $this->extractTextFromResponse($response);

            // Parse JSON
            $data = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to parse JSON response: ' . json_last_error_msg() . '. Response: ' . $text);
            }

            return [
                'success' => true,
                'data' => $data,
                'raw_response' => $text,
                'usage' => $response['usageMetadata'] ?? null
            ];

        } catch (Exception $e) {
            error_log("Gemini structured data extraction error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Simple text generation (non-JSON)
     * 
     * @param string $prompt The prompt text
     * @param array $options Optional parameters
     * @return string Generated text
     * @throws Exception If API call fails
     */
    public function generate(string $prompt, array $options = []): string
    {
        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];

        $response = $this->chat($messages, $options);
        return $this->extractTextFromResponse($response);
    }

    /**
     * Convert OpenAI-style messages to Gemini format
     * 
     * @param array $messages OpenAI format messages
     * @return array Gemini format contents
     */
    private function convertMessagesToGeminiFormat(array $messages): array
    {
        $contents = [];
        $systemContent = '';

        foreach ($messages as $message) {
            $role = $message['role'];
            $content = $message['content'];

            // Gemini doesn't have a separate "system" role, so prepend it to first user message
            if ($role === 'system') {
                $systemContent = $content . "\n\n";
                continue;
            }

            // Map roles: user -> user, assistant -> model
            $geminiRole = ($role === 'assistant') ? 'model' : 'user';

            // If this is the first user message and we have system content, prepend it
            if ($geminiRole === 'user' && !empty($systemContent) && empty($contents)) {
                $content = $systemContent . $content;
                $systemContent = '';
            }

            $contents[] = [
                'role' => $geminiRole,
                'parts' => [
                    ['text' => $content]
                ]
            ];
        }

        return $contents;
    }

    /**
     * Extract text content from Gemini response
     * 
     * @param array $response Gemini API response
     * @return string Extracted text
     * @throws Exception If response format is unexpected
     */
    private function extractTextFromResponse(array $response): string
    {
        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception('Unexpected Gemini API response format: ' . json_encode($response));
        }

        return $response['candidates'][0]['content']['parts'][0]['text'];
    }

    /**
     * Make HTTP request with automatic retry logic
     * 
     * @param string $model Model name (e.g., 'gemini-pro')
     * @param array $payload Request payload
     * @return array API response
     * @throws Exception If all retries fail
     */
    private function makeRequestWithRetry(string $model, array $payload): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                // Gemini API endpoint format
                $endpoint = "{$this->apiBaseUrl}/models/{$model}:generateContent?key={$this->apiKey}";

                $headers = [
                    'Content-Type: application/json',
                ];

                $response = $this->httpClient->post(
                    $endpoint,
                    $payload,
                    $headers
                );

                // Check for API errors
                if (isset($response['error'])) {
                    throw new Exception(
                        'Gemini API error: ' . ($response['error']['message'] ?? json_encode($response['error']))
                    );
                }

                // Success
                return $response;

            } catch (Exception $e) {
                $lastException = $e;
                error_log("Gemini API attempt {$attempt}/{$this->maxRetries} failed: " . $e->getMessage());

                // Don't retry on certain errors
                $errorMessage = $e->getMessage();
                if (
                    str_contains($errorMessage, 'invalid_api_key') ||
                    str_contains($errorMessage, 'authentication') ||
                    str_contains($errorMessage, 'API key')
                ) {
                    throw $e;
                }

                // Wait before retry (exponential backoff)
                if ($attempt < $this->maxRetries) {
                    $waitTime = pow(2, $attempt - 1); // 1s, 2s, 4s...
                    sleep($waitTime);
                }
            }
        }

        // All retries failed
        throw new Exception(
            "Gemini API request failed after {$this->maxRetries} attempts. Last error: " . 
            ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }

    /**
     * Get current model name
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get API configuration info (for debugging)
     */
    public function getConfigInfo(): array
    {
        return [
            'configured' => $this->isConfigured(),
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'api_base_url' => $this->apiBaseUrl,
        ];
    }
}
