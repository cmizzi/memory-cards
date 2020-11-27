<?php

namespace App\GameStorage;

use App\GameState;

interface Storage
{
	/**
	 * Retrieve the game storage from the store.
	 *
	 * @return GameState|null
	 */
	public function get(): ?GameState;

	/**
	 * Store the game state from the store.
	 *
	 * @param GameState $state
	 * @return $this
	 */
	public function set(GameState $state): self;
}