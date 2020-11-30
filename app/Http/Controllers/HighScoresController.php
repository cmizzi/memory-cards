<?php

namespace App\Http\Controllers;

class HighScoresController implements Controller
{
	/**
	 * @return \array[][]
	 */
	public function index(): array
	{
		return [
			"scores" => [
				[
					"name"  => "Cyril Mizzi",
					"score" => 86,
				],
				[
					"name"  => "Cyril Mizzi",
					"score" => 88,
				],
			]
		];
	}
}