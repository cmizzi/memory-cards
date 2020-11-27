<?php

namespace App\Support\Router\Exceptions;

use Throwable;

class RouteNotFoundException extends \Exception
{
	/**
	 * @param  string  $path
	 */
	public function __construct(string $path)
	{
		parent::__construct("No route match \"{$path}\" path.");
	}
}