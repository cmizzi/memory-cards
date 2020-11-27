<?php

namespace App\Http\Exceptions;

class RedirectException extends HttpException
{
	/**
	 * @var string
	 */
	public string $path;

	/**
	 * RedirectException constructor.
	 * @param  string  $path
	 * @param  int  $status
	 */
	public function __construct(string $path, int $status)
	{
		$this->path = $path;

		parent::__construct("This is a redirection exception.", $status);
	}

	/**
	 * @param  string  $path
	 * @param  int  $status
	 * @return static
	 */
	static public function to(string $path, int $status = 302)
	{
		return new static($path, $status);
	}
}