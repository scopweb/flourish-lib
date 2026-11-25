<?php
/**
 * HTTP Client Service
 *
 * Modern HTTP client for making requests to external APIs and services
 * with comprehensive support for headers, authentication, middleware and
 * response handling.
 *
 * The client is implemented with plain cURL, so it has no external
 * dependencies, while offering a PSR-18-like surface:
 *
 * {{{
 * #!php
 * $client = new fHttpClient('https://api.example.com');
 * $client->setDefaultHeaders(array('Accept' => 'application/json'));
 *
 * $response = $client->get('/users');
 * if ($response->ok) {
 *     $users = $response->json();
 * }
 * }}}
 *
 * ✅ **PHP 8.4 / 8.5 COMPATIBLE** ✅
 * Uses native PHP streams and cURL for reliable HTTP communication
 *
 * @copyright  Copyright (c) 2025 Claude Code Migration
 * @author     Claude Code Migration <noreply@anthropic.com>
 * @license    http://flourishlib.com/license
 * @package    Flourish
 * @link       http://flourishlib.com/fHttpClient
 * @version    2.0.0 - HTTP Client Service
 */

require_once __DIR__ . '/fException.php';
require_once __DIR__ . '/fUnexpectedException.php';
require_once __DIR__ . '/fConnectivityException.php';
require_once __DIR__ . '/fHttpResponse.php';

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
     * Headers sent with every request
     *
     * @var array
     */
    private array $default_headers = [];

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
     * The base URL used to resolve relative URLs
     *
     * @var string
     */
    private string $base_url = '';

    /**
     * Whether redirects should be followed
     *
     * @var boolean
     */
    private bool $follow_redirects = true;

    /**
     * The middleware run before a request is sent
     *
     * @var array
     */
    private array $request_middleware = [];

    /**
     * The middleware run after a response has been received
     *
     * @var array
     */
    private array $response_middleware = [];

    /**
     * The path of the CA bundle used to verify SSL certificates
     *
     * @var string|null
     */
    private ?string $ca_bundle = null;

    /**
     * Constructor
     *
     * The following options are recognized, any other option is ignored:
     *
     *  - timeout:         The timeout in seconds
     *  - user_agent:      The User-Agent header to send
     *  - follow_redirects: Whether redirects should be followed
     *  - headers:         Headers to send with every request
     *  - base_url:        The base URL used to resolve relative URLs
     *
     * @param string $url      The URL to request
     * @param array  $options  The client options
     */
    public function __construct(string $url = '', array $options = [])
    {
        $this->url = $url;
        $this->initializeDefaults();

        foreach ($options as $name => $value) {
            switch ($name) {
                case 'timeout':
                    $this->setTimeout((int) $value);
                    break;
                case 'user_agent':
                    $this->default_headers['User-Agent'] = (string) $value;
                    break;
                case 'follow_redirects':
                    $this->follow_redirects = (bool) $value;
                    break;
                case 'headers':
                    $this->setDefaultHeaders((array) $value);
                    break;
                case 'base_url':
                    $this->setBaseUrl((string) $value);
                    break;
            }
        }
    }

    /**
     * Initialize default headers and options
     *
     * @return void
     */
    private function initializeDefaults(): void
    {
        $this->default_headers = [
            'User-Agent' => 'Flourish-HttpClient/1.0',
            'Accept' => 'application/json, text/plain, */*',
        ];
    }

    /**
     * Sets the request URL
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
     * Sets the request method
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
     * Sets a header
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
     * Sets multiple headers
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
     * Sets the headers sent with every request
     *
     * @param array $headers  Array of header name => value pairs
     * @return self           For method chaining
     */
    public function setDefaultHeaders(array $headers): self
    {
        $this->default_headers = array_merge($this->default_headers, $headers);
        return $this;
    }

    /**
     * Returns the headers sent with every request
     *
     * @return array  Array of header name => value pairs
     */
    public function getDefaultHeaders(): array
    {
        return $this->default_headers;
    }

    /**
     * Returns a copy of the client with the given headers merged in
     *
     * The original client is left untouched.
     *
     * @param array $headers  Array of header name => value pairs
     * @return self           A new client instance
     */
    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->default_headers = array_merge($this->default_headers, $headers);
        return $clone;
    }

    /**
     * Sets the base URL used to resolve relative URLs
     *
     * @param string $base_url  The base URL
     * @return self             For method chaining
     */
    public function setBaseUrl(string $base_url): self
    {
        $this->base_url = rtrim($base_url, '/');
        return $this;
    }

    /**
     * Returns the base URL used to resolve relative URLs
     *
     * @return string  The base URL
     */
    public function getBaseUrl(): string
    {
        return $this->base_url;
    }

    /**
     * Sets basic authentication
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
     * Sets bearer token authentication
     *
     * @param string $token  The bearer token
     * @return self          For method chaining
     */
    public function setBearerToken(string $token): self
    {
        return $this->setHeader('Authorization', 'Bearer ' . $token);
    }

    /**
     * Sets the authentication to use for the requests
     *
     * The following types are supported:
     *
     *  - basic:  The username and password are used for basic authentication
     *  - bearer: The token is sent as a bearer token
     *
     * @param string       $type                The type of authentication
     * @param string       $username_or_token   The username or the token
     * @param string|null  $password            The password, for basic authentication
     * @return self                            For method chaining
     * @throws fUnexpectedException  When the type of authentication is not supported
     */
    public function setAuth(string $type, string $username_or_token, ?string $password = null): self
    {
        switch (strtolower($type)) {
            case 'basic':
                if ($password === null) {
                    throw new fUnexpectedException('A password is required for basic authentication');
                }
                return $this->setBasicAuth($username_or_token, $password);
            case 'bearer':
                return $this->setBearerToken($username_or_token);
        }
        throw new fUnexpectedException('The authentication type, ' . $type . ', is not supported');
    }

    /**
     * Sets request data/body
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
     * Sets request timeout
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
     * Sets cURL option
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
     * Adds a middleware run before the request is sent
     *
     * The middleware receives the request and the next handler and must return
     * the result of calling the next handler:
     *
     * {{{
     * #!php
     * $client->addMiddleware(function($request, $next) {
     *     $request['headers']['X-Trace'] = 'abc123';
     *     return $next($request);
     * });
     * }}}
     *
     * @param callable $middleware  The middleware to add
     * @return self                 For method chaining
     */
    public function addMiddleware(callable $middleware): self
    {
        $this->request_middleware[] = $middleware;
        return $this;
    }

    /**
     * Adds a middleware run after the response has been received
     *
     * The middleware receives the response and must return a response:
     *
     * {{{
     * #!php
     * $client->addResponseMiddleware(function($response) {
     *     return $response;
     * });
     * }}}
     *
     * @param callable $middleware  The middleware to add
     * @return self                 For method chaining
     */
    public function addResponseMiddleware(callable $middleware): self
    {
        $this->response_middleware[] = $middleware;
        return $this;
    }

    /**
     * Execute the request and get the response
     *
     * @return fHttpResponse  The response
     * @throws fConnectivityException  When the request fails
     * @throws fUnexpectedException  When the URL has not been set
     */
    public function execute(): fHttpResponse
    {
        return $this->request($this->method, $this->url, null, [], $this->data);
    }

    /**
     * Execute a GET request
     *
     * @param string|null $url      The URL, NULL to use the URL of the client
     * @param array       $headers  Additional headers for the request
     * @return fHttpResponse        The response
     */
    public function get(?string $url = null, array $headers = []): fHttpResponse
    {
        return $this->request('GET', $url, null, $headers);
    }

    /**
     * Execute a POST request
     *
     * @param string|array|null $url      The URL, NULL to use the URL of the client. An array is taken as the request body for backwards compatibility
     * @param mixed       $data    The request body, arrays are sent as JSON
     * @param array       $headers  Additional headers for the request
     * @return fHttpResponse        The response
     */
    public function post(string|array|null $url = null, mixed $data = null, array $headers = []): fHttpResponse
    {
        if (is_array($url)) {
            $data = $url;
            $url = null;
        }
        return $this->request('POST', $url, $data, $headers);
    }

    /**
     * Execute a PUT request
     *
     * @param string|array|null $url      The URL, NULL to use the URL of the client. An array is taken as the request body for backwards compatibility
     * @param mixed       $data    The request body, arrays are sent as JSON
     * @param array       $headers  Additional headers for the request
     * @return fHttpResponse        The response
     */
    public function put(string|array|null $url = null, mixed $data = null, array $headers = []): fHttpResponse
    {
        if (is_array($url)) {
            $data = $url;
            $url = null;
        }
        return $this->request('PUT', $url, $data, $headers);
    }

    /**
     * Execute a PATCH request
     *
     * @param string|array|null $url      The URL, NULL to use the URL of the client. An array is taken as the request body for backwards compatibility
     * @param mixed       $data    The request body, arrays are sent as JSON
     * @param array       $headers  Additional headers for the request
     * @return fHttpResponse        The response
     */
    public function patch(string|array|null $url = null, mixed $data = null, array $headers = []): fHttpResponse
    {
        if (is_array($url)) {
            $data = $url;
            $url = null;
        }
        return $this->request('PATCH', $url, $data, $headers);
    }

    /**
     * Execute a DELETE request
     *
     * @param string|null $url      The URL, NULL to use the URL of the client
     * @param array       $headers  Additional headers for the request
     * @return fHttpResponse        The response
     */
    public function delete(?string $url = null, array $headers = []): fHttpResponse
    {
        return $this->request('DELETE', $url, null, $headers);
    }

    /**
     * Execute a HEAD request
     *
     * @param string|null $url      The URL, NULL to use the URL of the client
     * @param array       $headers  Additional headers for the request
     * @return fHttpResponse        The response
     */
    public function head(?string $url = null, array $headers = []): fHttpResponse
    {
        return $this->request('HEAD', $url, null, $headers);
    }

    /**
     * Performs a request, running the middleware around it
     *
     * @param string      $method   The HTTP method
     * @param string|null $url      The URL, NULL to use the URL of the client
     * @param mixed       $data     The request body
     * @param array       $headers  Additional headers for the request
     * @param mixed       $raw_data An already serialized request body
     * @return fHttpResponse        The response
     * @throws fUnexpectedException  When no URL is available
     */
    private function request(
        string $method,
        ?string $url = null,
        mixed $data = null,
        array $headers = [],
        mixed $raw_data = null
    ): fHttpResponse {
        if ($url === null || $url === '') {
            $url = $this->url;
        }
        if ($url === '') {
            throw new fUnexpectedException('URL must be set before executing request');
        }

        $request_headers = array_merge($this->default_headers, $this->headers, $headers);
        $body = $raw_data;

        if ($body === null && $data !== null) {
            if (is_array($data) || is_object($data)) {
                $body = json_encode($data);
                if ($body === false) {
                    throw new fUnexpectedException('The request data could not be encoded as JSON');
                }
                $request_headers['Content-Type'] = 'application/json';
            }
            else {
                $body = (string) $data;
            }
        }
        if ($body === null) {
            $body = '';
        }
        if (is_array($body)) {
            $body = json_encode($body);
            $request_headers['Content-Type'] = 'application/json';
        }

        $request = [
            'method' => strtoupper($method),
            'url' => $this->buildUrl($url),
            'headers' => $request_headers,
            'body' => (string) $body,
        ];

        $chain = fn(array $req): fHttpResponse => $this->send($req);
        foreach (array_reverse($this->request_middleware) as $middleware) {
            $next = $chain;
            $chain = function (array $req) use ($middleware, $next): fHttpResponse {
                return $middleware($req, $next);
            };
        }

        $response = $chain($request);

        foreach ($this->response_middleware as $middleware) {
            $response = $middleware($response);
        }

        return $response;
    }

    /**
     * Combines a URL with the base URL of the client
     *
     * Absolute URLs are returned untouched.
     *
     * @param string $url  The URL
     * @return string       The full URL
     */
    private function buildUrl(string $url): string
    {
        if (preg_match('#^[a-z][a-z0-9+.\-]*://#i', $url) === 1) {
            return $url;
        }
        if ($this->base_url === '') {
            return $url;
        }
        return $this->base_url . '/' . ltrim($url, '/');
    }

    /**
     * Sends a request with cURL and builds the response
     *
     * @param array $request  The request (method, url, headers and body)
     * @return fHttpResponse  The response
     * @throws fConnectivityException  When the request fails
     */
    private function send(array $request): fHttpResponse
    {
        if (!function_exists('curl_init')) {
            throw new fConnectivityException('cURL extension is not installed');
        }

        $method = strtoupper((string) ($request['method'] ?? 'GET'));
        $url = (string) ($request['url'] ?? '');
        $headers = (array) ($request['headers'] ?? []);
        $body = (string) ($request['body'] ?? '');

        $curl = curl_init($url);
        if ($curl === false) {
            throw new fConnectivityException('The cURL handle could not be created');
        }

        $response_headers = [];

        $header_lines = [];
        foreach ($headers as $name => $value) {
            $header_lines[] = $name . ': ' . $value;
        }

        $curl_options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => $this->follow_redirects,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $header_lines,
            CURLOPT_HEADERFUNCTION => function ($handle, $header) use (&$response_headers): int {
                $length = strlen($header);
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $name = trim($parts[0]);
                    if ($name !== '' && stripos($name, 'HTTP/') !== 0) {
                        $response_headers[$name][] = trim($parts[1]);
                    }
                }
                return $length;
            },
        ];

        if ($method === 'HEAD') {
            $curl_options[CURLOPT_NOBODY] = true;
        }
        elseif ($body !== '') {
            $curl_options[CURLOPT_POSTFIELDS] = $body;
        }

        $ca_bundle = $this->getCaBundle();
        if ($ca_bundle !== null && !isset($this->options[CURLOPT_CAINFO]) && !isset($this->options[CURLOPT_CAPATH])) {
            $curl_options[CURLOPT_CAINFO] = $ca_bundle;
        }

        curl_setopt_array($curl, $curl_options);

        // Apply custom options, they take precedence over the defaults
        if ($this->options) {
            curl_setopt_array($curl, $this->options);
        }

        $response_body = curl_exec($curl);
        $error_number = curl_errno($curl);
        $error_message = curl_error($curl);
        $info = curl_getinfo($curl);

        if ($error_number) {
            throw new fConnectivityException('HTTP request failed: ' . $error_message);
        }

        return new fHttpResponse(
            (int) ($info['http_code'] ?? 0),
            $response_headers,
            is_string($response_body) ? $response_body : '',
            $info
        );
    }

    /**
     * Finds a CA bundle that can be used to verify SSL certificates
     *
     * The bundle is only used when the user has not configured one through
     * the CURLOPT_CAINFO or CURLOPT_CAPATH cURL options. SSL verification is
     * never disabled.
     *
     * @return string|null  The path of the CA bundle, NULL when none was found
     */
    private function getCaBundle(): ?string
    {
        if ($this->ca_bundle !== null) {
            return $this->ca_bundle === '' ? null : $this->ca_bundle;
        }

        $candidates = [];

        foreach (['SSL_CERT_FILE', 'CURL_CA_BUNDLE', 'REQUESTS_CA_BUNDLE'] as $variable) {
            $value = getenv($variable);
            if (is_string($value) && $value !== '') {
                $candidates[] = $value;
            }
        }

        $ini_value = ini_get('curl.cainfo');
        if (is_string($ini_value) && $ini_value !== '') {
            $candidates[] = $ini_value;
        }

        if (defined('PHP_BINDIR')) {
            $candidates[] = PHP_BINDIR . DIRECTORY_SEPARATOR . 'cacert.pem';
            $candidates[] = dirname(PHP_BINDIR) . DIRECTORY_SEPARATOR . 'extras'
                . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'cacert.pem';
        }

        $candidates = array_merge($candidates, [
            'C:\\Program Files\\Git\\usr\\ssl\\cert.pem',
            'C:\\Program Files\\Git\\mingw64\\etc\\ca-bundle.crt',
            'C:\\Program Files\\Git\\etc\\pki\\ca-trust\\extracted\\openssl\\ca-bundle.trust.crt',
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/etc/ssl/ca-bundle.pem',
            '/etc/ssl/cert.pem',
            '/usr/local/etc/openssl/cert.pem',
            '/usr/local/etc/openssl@3/cert.pem',
            '/opt/homebrew/etc/openssl@3/cert.pem',
            '/usr/local/share/ca-certificates/mozilla/cacert.pem',
        ]);

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && is_file($candidate) && is_readable($candidate) && filesize($candidate) > 0) {
                $this->ca_bundle = $candidate;
                return $candidate;
            }
        }

        $this->ca_bundle = '';
        return null;
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
