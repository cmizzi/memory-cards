<?php

namespace App;

use App\Exceptions\EmptyGameStateException;
use App\GameStorage\SessionStorage;
use App\GameStorage\Storage;
use App\Exceptions\GameActionNotFound;
use JetBrains\PhpStorm\Pure;

/**
 * Cette classe est une interface entre un état de jeu et le client. Il a pour charge de limiter l'interaction à
 * seulement ce qui est réalisable par le client.
 */
class Game
{
	/**
	 * Stockage du plateau. Par défaut, lorsqu'un client se connecte, le plateau est stocké dans la session de
	 * l'utilisateur. Pour les tests unitaires, puisque les sessions n'existent pas, nous stockons le plateau en
	 * mémoire.
	 *
	 * @var Storage
	 */
	private Storage $storage;

	/**
	 * Plateau de jeu (récupéré depuis le stockage ou bien nouvellement généré).
	 *
	 * @var GameState|null
	 */
	private ?GameState $state = null;

	/**
	 * @param Storage|null $storage
	 */
	#[Pure]
	public function __construct(?Storage $storage = null)
	{
		// Par défaut, nous souhaitons que le stockage soit dans la session.
		$this->storage = $storage ?? new SessionStorage("game:state");
	}

	/**
	 * Execute une action envoyée par le client. L'argument `$with` est optionnel, et permet d'envoyer depuis le client
	 * des informations complémentaires par rapport à l'action : par exemple, `reveal` demande forcément l'identifiant
	 * de la carte à retourner, alors que l'action `reset` ne demande pas de paramètre particulier.
	 *
	 * @param  string $action
	 * @param  mixed|null $with
	 * @return GameState
	 * @throws GameActionNotFound
	 */
	public function run(string $action, mixed $with = null): GameState
	{
		// Seulement deux actions sont disponibles : (re)-démarrer le jeu (`reset`) ou retourner une carte (`reveal`).
		return match ($action) {
			"reset"  => $this->getState()->reset(),
			"reveal" => $this->getState()->reveal($with),
			default  => throw new GameActionNotFound("The game action does not exist."),
		};
	}

	/**
	 * Charge le plateau depuis le stockage, ou s'il n'existe pas, en créé un nouveau.
	 *
	 * @return GameState
	 */
	public function getState(): GameState
	{
		if ($this->state === null) {
			// Essayons de récupérer le plateau depuis le stockage.
			if ($state = $this->storage->get()) {
				$this->state = $state;
			}

			// Il n'existe pas : créons en un nouveau.
			else {
				$this->state = new GameState;
			}
		}

		return $this->state;
	}

	/**
	 * Sauvegarde le plateau dans le stockage.
	 *
	 * @return $this
	 * @throws EmptyGameStateException
	 */
	public function saveState(): self
	{
		// Nous devons d'abord vérifier que le plateau existe : si aucun appel à `getState` n'a été effectué, alors
		// nous n'avons pas chargé un plateau. Si un plateau n'existe pas, il ne peut pas être sauvegardé.
		if ($this->state === null) {
			throw new EmptyGameStateException("Cannot save the state because it is empty.");
		}

		// Demandons au stockage de sauvegarder le plateau.
		$this->storage->set($this->state);

		return $this;
	}
}