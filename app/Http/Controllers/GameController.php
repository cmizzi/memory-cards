<?php

namespace App\Http\Controllers;

use App\Exceptions\EmptyGameStateException;
use App\Game;
use App\Exceptions\GameActionNotFound;
use App\Http\Exceptions\HttpException;
use JetBrains\PhpStorm\ArrayShape;

class GameController implements Controller
{
	/**
	 * Entrée principale utilisée par le client : celui-ci permet d'effectuer des actions de jeu.
	 *
	 * @return array
	 * @throws HttpException
	 */
	#[ArrayShape(["hash" => "string", "board" => "array", "current_card" => "int|null", "is_winner" => "bool", "is_party_over" => "bool"])]
	public function store(): array
	{
		$game = new Game;

		try {
			$state = $game->run($_REQUEST["action"]);
			$game->saveState();
		}

		// L'action du jeu envoyée par le client n'existe pas.
		catch (GameActionNotFound) {
			throw new HttpException("Cannot apply action \"{$_REQUEST["action"]}\".", 422);
		}

		// Le jeu ne peut pas être sauvegardé.
		catch (EmptyGameStateException) {
			throw new HttpException("The game state has not been saved. Maybe an internal error ?", 500);
		}

		// Retournons plusieurs valeurs au client, afin qu'il ait toutes les informations pour avoir un rendu correct de
		// l'état en cours du plateau.
		return [
			// Une chaîne de caractère unique représentant l'état du plateau.
			"hash" => $state->getBoardHash(),

			// Le plateau (sans retourner un type valide pour les cartes qui ne sont pas encore visibles).
			"board" => $state->getBoard(),

			// Le type de carte jouée par l'utilisateur. Cette valeur sera vide si l'action est `reset`.
			"current_card" => $state->getCurrentCard(),

			// Est-ce que la joueur a gagné ?
			"is_winner" => $state->isWinner(),

			// Est-ce que la partie est terminée ?
			"is_party_over" => $state->isPartyOver(),
		];
	}
}