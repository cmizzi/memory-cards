<?php

namespace App\Http\Controllers;

use App\Models\Score;
use JetBrains\PhpStorm\ArrayShape;

class HighScoresController implements Controller
{
	/**
	 * @return \array[][]
	 */
	#[ArrayShape(["scores" => "array"])]
	public function index(): array
	{
		return [
			"scores" => Score::get(),
		];
	}
}