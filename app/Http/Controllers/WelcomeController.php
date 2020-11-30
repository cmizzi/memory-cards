<?php

namespace App\Http\Controllers;

class WelcomeController implements Controller
{
	/**
	 * Implements the controller logic.
	 *
	 * @return string
	 */
	public function index(): string
	{
		// As our front is generated and not handle by PHP, we can't simply generate the content here but instead, we
		// want return the content of our JS generated page.
		return file_get_contents(__DIR__ . "/" . "../../../public/dist/index.html");
	}
}