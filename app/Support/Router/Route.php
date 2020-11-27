<?php

namespace App\Support\Router;

class Route
{
	/**
	 * @var string
	 */
	private string $path;

	/**
	 * @var mixed
	 */
	private mixed $callable;

	/**
	 * @var string
	 */
	private string $method;

	/**
	 * @param  string  $method
	 * @param  string  $path
	 * @param  mixed  $callable
	 */
	public function __construct(string $method, string $path, mixed $callable)
	{
		$this->path = $path;
		$this->callable = $callable;
		$this->method = $method;
	}

	/**
	 * @param  string  $path
	 * @return bool
	 */
	public function match(string $path): bool
	{
		return $path === $this->path;
	}

	/**
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getMethod(): string
	{
		return $this->method;
	}
}