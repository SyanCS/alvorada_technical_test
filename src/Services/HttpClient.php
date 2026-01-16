<?php

namespace App\Services;

use App\Config\AppConfig;
use Exception;

/**
 * HTTP Client
 * Reusable cURL-based HTTP client for API requests
 * Follows Single Responsibility Principle
 * Configuration loaded from centralized config
 */
class HttpClient
{
    private int $timeout;
    private string $userAgent;
    private array $defaultHeaders;
    private bool $followLocation;
    private bool $verifySsl;

    public function __construct(
        ?string $userAgent = null,
        ?int $timeout = null,
        ?bool $followLocation = null,
        ?bool $verifySsl = null
    ) {
        $config = AppConfig::getInstance();
        
        // Load from parameters or config defaults
        $this->userAgent = $userAgent ?? $config->get('http.user_agent');
        $this->timeout = $timeout ?? $config->get('http.timeout');
        $this->followLocation = $followLocation ?? $config->get('http.follow_redirects');
        $this->verifySsl = $verifySsl ?? $config->get('http.verify_ssl');
        
        $this->defaultHeaders = [
            'Accept: application/json',
            'Accept-Language: en-US,en;q=0.9',
        ];
    }

    /**
     * Perform GET request
     * 
     * @param string $url
     * @param array $params Query parameters
     * @param array $headers Additional headers
     * @return array Response data
     * @throws Exception
     */
    public function get(string $url, array $params = [], array $headers = []): array
    {
        if (!empty($params)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
        }

        return $this->request('GET', $url, null, $headers);
    }

    /**
     * Perform POST request
     * 
     * @param string $url
     * @param mixed $data
     * @param array $headers Additional headers
     * @return array Response data
     * @throws Exception
     */
    public function post(string $url, $data = null, array $headers = []): array
    {
        return $this->request('POST', $url, $data, $headers);
    }

    /**
     * Perform PUT request
     * 
     * @param string $url
     * @param mixed $data
     * @param array $headers Additional headers
     * @return array Response data
     * @throws Exception
     */
    public function put(string $url, $data = null, array $headers = []): array
    {
        return $this->request('PUT', $url, $data, $headers);
    }

    /**
     * Perform DELETE request
     * 
     * @param string $url
     * @param array $headers Additional headers
     * @return array Response data
     * @throws Exception
     */
    public function delete(string $url, array $headers = []): array
    {
        return $this->request('DELETE', $url, null, $headers);
    }

    /**
     * Generic request method
     * 
     * @param string $method HTTP method
     * @param string $url
     * @param mixed $data Request body
     * @param array $headers Additional headers
     * @return array Response data
     * @throws Exception
     */
    private function request(string $method, string $url, $data = null, array $headers = []): array
    {
        $ch = curl_init();

        // Merge headers
        $allHeaders = array_merge($this->defaultHeaders, $headers);

        // Basic options
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_FOLLOWLOCATION => $this->followLocation,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_CUSTOMREQUEST => $method,
        ];

        // Add request body for POST/PUT
        if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            if (is_array($data)) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
                $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
            } else {
                $options[CURLOPT_POSTFIELDS] = $data;
            }
        }

        curl_setopt_array($ch, $options);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        
        curl_close($ch);

        // Check for cURL errors
        if ($curlErrno !== 0) {
            throw new Exception("cURL Error ({$curlErrno}): {$curlError}");
        }

        // Decode JSON response
        $data = json_decode($response, true);

        // Return raw response if not JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Check HTTP response code for non-JSON responses
            if ($httpCode < 200 || $httpCode >= 300) {
                throw new Exception("HTTP Error: API returned status code {$httpCode}. Response: {$response}");
            }
            return [
                'raw' => $response,
                'http_code' => $httpCode
            ];
        }

        // For JSON responses, include error in data if status code indicates error
        if ($httpCode < 200 || $httpCode >= 300) {
            // Return the error response as data so the caller can handle it
            return array_merge($data, ['http_code' => $httpCode]);
        }

        return $data;
    }

    /**
     * Set custom timeout
     * 
     * @param int $timeout
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Set custom user agent
     * 
     * @param string $userAgent
     * @return self
     */
    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * Add default header
     * 
     * @param string $header
     * @return self
     */
    public function addDefaultHeader(string $header): self
    {
        $this->defaultHeaders[] = $header;
        return $this;
    }

    /**
     * Set SSL verification
     * 
     * @param bool $verify
     * @return self
     */
    public function setVerifySsl(bool $verify): self
    {
        $this->verifySsl = $verify;
        return $this;
    }
}

