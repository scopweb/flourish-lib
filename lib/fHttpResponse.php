<?php
/**
 * HTTP Response
 *
 * Immutable value object wrapping the result of an HTTP request performed
 * by the fHttpClient. It offers a PSR-18-like surface (status code, headers,
 * body, JSON decoding) without requiring any external dependency.
 *
 * For backwards compatibility with the array returned by fHttpClient::execute()
 * in older versions, the response is also accessible as an array:
 *
 * {{{
 * #!php
 * $response = $client->get('https://example.com');
 * echo $response['body'];
 * echo $response->body;
 * }}}
 *
 * ✅ **PHP 8.4 / 8.5 COMPATIBLE** ✅
 *
 * @copyright  Copyright (c) 2025 Claude Code Migration
 * @author     Claude Code Migration <noreply@anthropic.com>
 * @license    http://flourishlib.com/license
 * @package    Flourish
 * @link       http://flourishlib.com/fHttpResponse
 * @version    1.0.0 - HTTP Response
 */

require_once __DIR__ . '/fException.php';
require_once __DIR__ . '/fExpectedException.php';
require_once __DIR__ . '/fUnexpectedException.php';
require_once __DIR__ . '/fValidationException.php';

class fHttpResponse implements ArrayAccess
{
	/**
	 * The HTTP status code
	 *
	 * @var integer
	 */
	private int $status_code;

	/**
	 * The response headers, keyed by header name with a list of values
	 *
	 * @var array
	 */
	private array $headers;

	/**
	 * The raw response body
	 *
	 * @var string
	 */
	private string $body;

	/**
	 * Additional information about the request/response (cURL info)
	 *
	 * @var array
	 */
	private array $info;

	/**
	 * The decoded JSON body, NULL until it has been decoded
	 *
	 * @var mixed
	 */
	private mixed $json_data = null;

	/**
	 * Whether the body has already been decoded as JSON
	 *
	 * @var boolean
	 */
	private bool $json_decoded = false;

	/**
	 * The descriptive text of the most common HTTP status codes
	 *
	 * @var array
	 */
	private const STATUS_TEXTS = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		103 => 'Early Hints',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		208 => 'Already Reported',
		226 => 'IM Used',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Payload Too Large',
		414 => 'URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => "I'm a teapot",
		421 => 'Misdirected Request',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Too Early',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		451 => 'Unavailable For Legal Reasons',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		510 => 'Not Extended',
		511 => 'Network Authentication Required',
	);

	/**
	 * Constructor
	 *
	 * @param integer $status_code  The HTTP status code
	 * @param array   $headers      The response headers
	 * @param string  $body         The raw response body
	 * @param array   $info         Additional information about the request
	 */
	public function __construct(int $status_code, array $headers, string $body, array $info = array())
	{
		$this->status_code = $status_code;
		$this->headers = $this->normalizeHeaders($headers);
		$this->body = $body;
		$this->info = $info;
	}

	/**
	 * Normalizes the headers so that every header name maps to a list of values
	 *
	 * @param array $headers  The raw headers
	 * @return array           The normalized headers
	 */
	private function normalizeHeaders(array $headers): array
	{
		$normalized = array();
		foreach ($headers as $name => $value) {
			if (is_int($name)) {
				// A raw header line, e.g. "Content-Type: text/html"
				$parts = explode(':', (string) $value, 2);
				if (count($parts) !== 2) {
					continue;
				}
				$name = trim($parts[0]);
				$value = trim($parts[1]);
			}
			$name = trim((string) $name);
			if ($name === '') {
				continue;
			}
			foreach ((is_array($value) ? $value : array($value)) as $single) {
				$normalized[$name][] = (string) $single;
			}
		}
		return $normalized;
	}

	/**
	 * Returns the HTTP status code of the response
	 *
	 * @return integer  The status code
	 */
	public function getStatusCode(): int
	{
		return $this->status_code;
	}

	/**
	 * Returns all of the response headers
	 *
	 * @return array  The headers, keyed by header name
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * Returns the values of a single response header
	 *
	 * The header name is matched case-insensitively. An empty array is
	 * returned when the header is not present.
	 *
	 * @param string $name  The name of the header
	 * @return array        The values of the header
	 */
	public function getHeader(string $name): array
	{
		foreach ($this->headers as $header_name => $values) {
			if (strcasecmp($header_name, $name) === 0) {
				return $values;
			}
		}
		return array();
	}

	/**
	 * Returns the raw response body
	 *
	 * @return string  The response body
	 */
	public function getBody(): string
	{
		return $this->body;
	}

	/**
	 * Returns additional information about the request
	 *
	 * @return array  The request information
	 */
	public function getInfo(): array
	{
		return $this->info;
	}

	/**
	 * Returns whether the request was successful
	 *
	 * @return boolean  TRUE if the status code is between 200 and 299
	 */
	public function isSuccessful(): bool
	{
		return $this->status_code >= 200 && $this->status_code < 300;
	}

	/**
	 * Returns whether the request resulted in a client error
	 *
	 * @return boolean  TRUE if the status code is between 400 and 499
	 */
	public function isClientError(): bool
	{
		return $this->status_code >= 400 && $this->status_code < 500;
	}

	/**
	 * Returns whether the request resulted in a server error
	 *
	 * @return boolean  TRUE if the status code is 500 or greater
	 */
	public function isServerError(): bool
	{
		return $this->status_code >= 500;
	}

	/**
	 * Decodes the response body as JSON
	 *
	 * @return mixed  The decoded JSON data
	 * @throws fValidationException  When the body does not contain valid JSON
	 */
	public function json(): mixed
	{
		if (!$this->json_decoded) {
			try {
				$this->json_data = json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
			}
			catch (JsonException $e) {
				throw new fValidationException('Invalid JSON response: ' . $e->getMessage());
			}
			$this->json_decoded = true;
		}
		return $this->json_data;
	}

	/**
	 * Returns the descriptive text of the status code
	 *
	 * @return string  The status text, e.g. "OK" or "Not Found"
	 */
	public function getStatusText(): string
	{
		return self::STATUS_TEXTS[$this->status_code] ?? 'Unknown Status';
	}

	/**
	 * Provides read access to the most common response properties
	 *
	 * @param string $name  The name of the property
	 * @return mixed        The value of the property
	 * @throws fUnexpectedException  When the property does not exist
	 */
	public function __get(string $name): mixed
	{
		switch ($name) {
			case 'status':
				return $this->getStatusCode();
			case 'ok':
				return $this->isSuccessful();
			case 'statusText':
				return $this->getStatusText();
			case 'body':
				return $this->getBody();
			case 'headers':
				return $this->getHeaders();
		}
		throw new fUnexpectedException('The property, ' . $name . ', does not exist on fHttpResponse');
	}

	/**
	 * Provides isset() support for the response properties
	 *
	 * @param string $name  The name of the property
	 * @return boolean       TRUE if the property exists
	 */
	public function __isset(string $name): bool
	{
		return in_array(
			$name,
			array('status', 'ok', 'statusText', 'body', 'headers'),
			true
		);
	}

	/**
	 * Returns whether the given offset exists
	 *
	 * @param mixed $offset  The offset to check
	 * @return boolean       TRUE if the offset exists
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists(mixed $offset): bool
	{
		return in_array(
			$offset,
			array('status', 'ok', 'statusText', 'body', 'headers', 'info'),
			true
		);
	}

	/**
	 * Returns the value of the given offset
	 *
	 * @param mixed $offset  The offset to read
	 * @return mixed         The value of the offset
	 * @throws fUnexpectedException  When the offset does not exist
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet(mixed $offset): mixed
	{
		switch ($offset) {
			case 'status':
				return $this->getStatusCode();
			case 'ok':
				return $this->isSuccessful();
			case 'statusText':
				return $this->getStatusText();
			case 'body':
				return $this->getBody();
			case 'headers':
				return $this->getHeaders();
			case 'info':
				return $this->getInfo();
		}
		throw new fUnexpectedException('The offset, ' . (is_scalar($offset) ? (string) $offset : gettype($offset)) . ', does not exist on fHttpResponse');
	}

	/**
	 * Sets the value of the given offset
	 *
	 * The response is immutable, so this always throws an exception.
	 *
	 * @param mixed $offset  The offset to write
	 * @param mixed $value   The value to write
	 * @return void
	 * @throws fUnexpectedException  Always, since the response is immutable
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet(mixed $offset, mixed $value): void
	{
		throw new fUnexpectedException('fHttpResponse is immutable and cannot be modified');
	}

	/**
	 * Removes the given offset
	 *
	 * The response is immutable, so this always throws an exception.
	 *
	 * @param mixed $offset  The offset to unset
	 * @return void
	 * @throws fUnexpectedException  Always, since the response is immutable
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset(mixed $offset): void
	{
		throw new fUnexpectedException('fHttpResponse is immutable and cannot be modified');
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
