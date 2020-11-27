<?php

namespace App;

use App\Exceptions\EmptyGameStateException;
use App\GameStorage\SessionStorage;
use App\GameStorage\Storage;
use App\Exceptions\GameActionNotFound;
use JetBrains\PhpStorm\Pure;

class Game
{
	/**
	 * @var Storage
	 */
	private Storage $storage;

	/**
	 * @var GameState|null
	 */
	private ?GameState $state = null;

	/**
	 * @param Storage|null $storage
	 */
	#[Pure] public function __construct(?Storage $storage = null)
	{
		$this->storage = $storage ?? new SessionStorage("game:state");
	}

	/**
	 * @param string $action
	 * @param mixed|null $with
	 * @return GameState
	 * @throws GameActionNotFound
	 */
	public function run(string $action, mixed $with = null): GameState
	{
		return match ($action) {
			"reset"  => $this->reset(),
			"reveal" => $this->revealCard($with),
			default  => throw new GameActionNotFound("The game action does not exist."),
		};
	}

	/**
	 * Reset the game.
	 */
	private function reset(): GameState
	{
		return $this->getState()->reset();
	}

	/**
	 * @param  int $cardIndex
	 * @return GameState
	 */
	private function revealCard(int $cardIndex): GameState
	{
		return $this->getState()->reveal($cardIndex);
	}

	/**
	 * Load the state, or create a new one if no one is stored into the storage.
	 *
	 * @return GameState
	 */
	public function getState(): GameState
	{
		if ($this->state === null) {
			if ($state = $this->storage->get()) {
				$this->state = $state;
			} else {
				$this->state = new GameState;
			}
		}

		return $this->state;
	}

	/**
	 * Save the current state.
	 *
	 * @return $this
	 * @throws EmptyGameStateException
	 */
	public function saveState(): self
	{
		if ($this->state === null) {
			throw new EmptyGameStateException("Cannot save the state because it is empty.");
		}

		$this->storage->set($this->state);

		return $this;
	}
}