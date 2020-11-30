<?php

namespace App\Http\Exceptions;

class RouteNotFoundException extends HttpException
{
	/**
	 * @param string|null $message
	 * @param int $status
	 */
	public function __construct(?string $message = null, int $status = 404)
	{
		if ($message === null) {
			$message = "Oops, the page you're looking for doesn't exist.";
		}

		parent::__construct($message, $status);
	}
}