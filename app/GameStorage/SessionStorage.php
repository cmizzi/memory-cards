<?php

namespace App\GameStorage;

use App\GameState;

class SessionStorage implements Storage
{
	/**
	 * @var string
	 */
	private string $key;

	/**
	 * SessionStorage constructor.
	 * @param string $key
	 */
	public function __construct(string $key)
	{
		$this->key = $key;
	}

	/**
	 * Store the game state into the session.
	 *
	 * @param  GameState $state
	 * @return $this
	 */
	public function set(GameState $state): self
	{
		$_SESSION[$this->key] = serialize($state);
	}

	/**
	 * Get the game state from the session.
	 *
	 * @return ?GameState
	 */
	public function get(): ?GameState
	{
		if (!empty($_SESSION[$this->key])) {
			return unserialize($_SESSION[$this->key]);
		}

		return null;
	}
}