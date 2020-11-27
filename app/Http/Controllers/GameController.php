<?php

namespace App\Http\Controllers;

use App\Exceptions\EmptyGameStateException;
use App\Exceptions\InvalidBoardException;
use App\Game;
use App\Exceptions\GameActionNotFound;
use App\Http\Exceptions\HttpException;

class GameController implements Controller
{
	/**
	 * @return array
	 * @throws HttpException
	 */
	public function execute(): array
	{
		$game = new Game;

		try {
			$state = $game->run($_REQUEST["action"]);
			$game->saveState();
		} catch (GameActionNotFound) {
			throw new HttpException("Cannot apply action \"{$_REQUEST["action"]}\".", 422);
		} catch (EmptyGameStateException) {
			throw new HttpException("The game state has not been saved. Maybe an internal error ?", 500);
		} catch (InvalidBoardException) {
			throw new HttpException("The game state is invalid.", 500);
		}

		return [
			"hash" => $state->getBoardHash(),
			"board" => $state->getBoard(),
			"current_card" => $state->getCurrentCard(),
			"is_winner" => $state->isWinner(),
			"is_party_over" => $state->isPartyOver(),
		];
	}
}