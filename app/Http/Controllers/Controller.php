<?php

namespace App\Http\Controllers;

use App\Http\Response;

interface Controller
{
	/**
	 * Execute the controller logic.
	 *
	 * @return string|array|Response
	 */
	public function execute(): string|array|Response;
}