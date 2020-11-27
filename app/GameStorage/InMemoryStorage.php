<?php

namespace App\GameStorage;

use App\GameState;

class InMemoryStorage implements Storage
{
	/**
	 * @var GameState|null
	 */
	private ?GameState $state = null;

	/**
	 * Retrieve the game state from memory.
	 *
	 * @return GameState|null
	 */
	public function get(): ?GameState
	{
		return $this->state;
	}

	/**
	 * Store the game state into the memory.
	 *
	 * @param GameState $state
	 * @return Storage
	 */
	public function set(GameState $state): Storage
	{
		$this->state = $state;
	}
}