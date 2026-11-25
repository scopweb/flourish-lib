<?php
/**
 * HTTP Client Service
 *
 * Modern HTTP client for making requests to external APIs and services
 * with comprehensive support for headers, authentication, and response handling
 *
 * ✅ **PHP 8.4 COMPATIBLE** ✅
 * Uses native PHP streams and cURL for reliable HTTP communication
 *
 * @copyright  Copyright (c) 2025 Claude Code Migration
 * @author     Claude Code Migration <noreply@anthropic.com>
 * @license    http://flourishlib.com/license
 * @package    Flourish
 * @link       http://flourishlib.com/fHttpClient
 * @version    1.0.0 - HTTP Client Service
 */

class fHttpClient
{
    /**
     * Request URL
     *
     * @var string
     */
    private string $url = '';

    /**
     * Request method (GET, POST, etc)
     *
     * @var string
     */
    private string $method = 'GET';

    /**
     * Request headers
     *
     * @var array
     */
    private array $headers = [];

    /**
     * Request body/data
     *
     * @var string|array
     */
    private string|array $data = '';

    /**
     * cURL options
     *
     * @var array
     */
    private array $options = [];

    /**
     * Response timeout in seconds
     *
     * @var integer
     */
    private int $timeout = 30;

    /**
     * Constructor
     *
     * @param string $url  The URL to request
     */
    public function __construct(string $url = '')
    {
        $this->url = $url;
        $this->initializeDefaults();
    }

    /**
     * Initialize default headers and options
     *
     * @return void
     */
    private function initializeDefaults(): void
    {
        $this->headers = [
            'User-Agent' => 'Flourish-HttpClient/1.0',
            'Accept' => 'application/json, text/plain, */*',
        ];
    }

    /**
     * Set the request URL
     *
     * @param string $url  The URL
     * @return self        For method chaining
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Set the request method
     *
     * @param string $method  The HTTP method (GET, POST, PUT, DELETE, etc)
     * @return self           For method chaining
     */
    public function setMethod(string $method): self
    {
        $this->method = strtoupper($method);
        return $this;
    }

    /**
     * Set a header
     *
     * @param string $name   Header name
     * @param string $value  Header value
     * @return self          For method chaining
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set multiple headers
     *
     * @param array $headers  Array of header name => value pairs
     * @return self           For method chaining
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Set basic authentication
     *
     * @param string $username  Username
     * @param string $password  Password
     * @return self             For method chaining
     */
    public function setBasicAuth(string $username, string $password): self
    {
        $credentials = base64_encode($username . ':' . $password);
        return $this->setHeader('Authorization', 'Basic ' . $credentials);
    }

    /**
     * Set bearer token authentication
     *
     * @param string $token  The bearer token
     * @return self          For method chaining
     */
    public function setBearerToken(string $token): self
    {
        return $this->setHeader('Authorization', 'Bearer ' . $token);
    }

    /**
     * Set request data/body
     *
     * @param string|array $data  The request data
     * @return self               For method chaining
     */
    public function setData(string|array $data): self
    {
        if (is_array($data)) {
            $this->data = json_encode($data);
            $this->setHeader('Content-Type', 'application/json');
        } else {
            $this->data = $data;
        }
        return $this;
    }

    /**
     * Set request timeout
     *
     * @param integer $seconds  Timeout in seconds
     * @return self             For method chaining
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Set cURL option
     *
     * @param integer $option  cURL option constant
     * @param mixed $value     Option value
     * @return self            For method chaining
     */
    public function setOption(int $option, mixed $value): self
    {
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * Execute the request and get response
     *
     * @return array  Response array with 'status', 'headers', 'body' keys
     * @throws fConnectivityException  When request fails
     */
    public function execute(): array
    {
        if (!function_exists('curl_init')) {
            throw new fConnectivityException('cURL extension is not installed');
        }

        if (empty($this->url)) {
            throw new fException('URL must be set before executing request');
        }

        $curl = curl_init($this->url);

        try {
            // Set default options
            curl_setopt_array($curl, [
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CUSTOMREQUEST => $this->method,
            ]);

            // Set request body for POST/PUT/PATCH
            if (!empty($this->data) && in_array($this->method, ['POST', 'PUT', 'PATCH'])) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $this->data);
            }

            // Set headers
            if (!empty($this->headers)) {
                $headerLines = [];
                foreach ($this->headers as $name => $value) {
                    $headerLines[] = $name . ': ' . $value;
                }
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headerLines);
            }

            // Apply custom options
            curl_setopt_array($curl, $this->options);

            // Execute request
            $body = curl_exec($curl);
            $errno = curl_errno($curl);
            $error = curl_error($curl);
            $info = curl_getinfo($curl);

            if ($errno) {
                throw new fConnectivityException('HTTP request failed: ' . $error);
            }

            return [
                'status' => (int)$info['http_code'],
                'headers' => $this->parseHeaders($info),
                'body' => $body,
            ];
        } finally {
            curl_close($curl);
        }
    }

    /**
     * Execute GET request
     *
     * @return array  Response array
     */
    public function get(): array
    {
        return $this->setMethod('GET')->execute();
    }

    /**
     * Execute POST request
     *
     * @param string|array $data  Optional post data
     * @return array              Response array
     */
    public function post(string|array $data = ''): array
    {
        if (!empty($data)) {
            $this->setData($data);
        }
        return $this->setMethod('POST')->execute();
    }

    /**
     * Execute PUT request
     *
     * @param string|array $data  Optional put data
     * @return array              Response array
     */
    public function put(string|array $data = ''): array
    {
        if (!empty($data)) {
            $this->setData($data);
        }
        return $this->setMethod('PUT')->execute();
    }

    /**
     * Execute DELETE request
     *
     * @return array  Response array
     */
    public function delete(): array
    {
        return $this->setMethod('DELETE')->execute();
    }

    /**
     * Parse response headers from cURL info
     *
     * @param array $info  cURL info array
     * @return array       Parsed headers
     */
    private function parseHeaders(array $info): array
    {
        return [
            'content-type' => $info['content_type'] ?? 'application/octet-stream',
            'content-length' => $info['content_length_download'] ?? 0,
        ];
    }
}

/**
 * Copyright (c) 2025 Claude Code Migration
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
