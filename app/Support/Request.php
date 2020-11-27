<?php

namespace App\Support;

/**
 * Warning: This implementation does not support any PSR standard.
 */
class Request
{
	/**
	 * @var string
	 */
	private string $path;

	/**
	 * @var array
	 */
	private array $headers;

	/**
	 * @var array
	 */
	private array $attributes;

	/**
	 * Construct a request.
	 *
	 * @param  string  $path
	 * @param  array  $attributes
	 * @param  array  $headers
	 */
	public function __construct(string $path, array $attributes = [], array $headers = [])
	{
		$this->path = $path;
		$this->headers = $headers;
		$this->attributes = $attributes;
	}

	/**
	 * Capture request from global variables.
	 *
	 * @return static
	 */
	static public function capture(): self
	{
		$parsed = parse_url($_SERVER["REQUEST_URI"]);

		return new Request(
			$parsed["path"],
			$_REQUEST,
			$_SERVER,
		);
	}

	/**
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * @param  string  $key
	 * @param  mixed|null  $default
	 * @return array
	 */
	public function getHeader(string $key, mixed $default = null): array
	{
		if (isset($this->headers[$key])) {
			return $this->headers[$key];
		}

		return $default;
	}

	/**
	 * Get the HTTP method.
	 *
	 * @return string
	 */
	public function getMethod(): string
	{
		return $this->getHeader("REQUEST_METHOD");
	}

	/**
	 * @param  string  $key
	 * @return bool
	 */
	public function has(string $key): bool
	{
		return isset($this->attributes[$key]);
	}

	/**
	 * @param  string  $key
	 * @param  mixed|null  $default
	 * @return mixed
	 */
	public function get(string $key, mixed $default = null): mixed
	{
		if ($this->has($key)) {
			return $this->attributes[$key];
		}

		return $default;
	}
}