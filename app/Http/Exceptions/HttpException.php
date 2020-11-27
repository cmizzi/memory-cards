<?php

namespace App\Http\Exceptions;

class HttpException extends \Exception
{
	/**
	 * @var int
	 */
	public int $status;

	/**
	 * RedirectException constructor.
	 * @param  string  $message
	 * @param  int  $status
	 */
	public function __construct(string $message, int $status)
	{
		$this->status = $status;

		parent::__construct($message);
	}
}