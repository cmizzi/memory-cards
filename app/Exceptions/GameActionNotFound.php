<?php

namespace App\Exceptions;

use App\Http\Exceptions\HttpException;

class GameActionNotFound extends HttpException
{
	/**
	 * @param string $message
	 */
	public function __construct(string $message)
	{
		parent::__construct($message, 500);
	}
}