<?php

namespace App\Http\Controllers;

use App\Http\Response;

class HighScoresController implements Controller
{
	/**
	 * @return \array[][]
	 */
	public function execute(): array
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