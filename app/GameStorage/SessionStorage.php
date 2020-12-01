<?php

namespace App\GameStorage;

use App\GameState;
use Throwable;

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
		// La fonction `serialize` transforme ses paramètres en quelque chose de stockable sous forme de chaîne de
		// caractères. A son inverse, `unserialize` récupère cette chaîne et reconstruit l'objet stocké.
		$_SESSION[$this->key] = serialize($state);

		return $this;
	}

	/**
	 * Get the game state from the session.
	 *
	 * @return ?GameState
	 */
	public function get(): ?GameState
	{
		if (!empty($_SESSION[$this->key])) {
			// La fonction `unserialize` est une fonction très spéciale en PHP : elle permet de récupérer une classe
			// stockée en mémoire ou dans un fichier (comme dans les sessions, par exemple) et restaure l'intégralité
			// des valeurs sans avoir besoin de reconstruire une nouvelle instance (appel à `new`).
			//
			// L'utilisation de `@` est très spécifique : la fonction `unserialize` ne retourne pas d'exception mais
			// une NOTICE PHP. Compte tenu de la simplicité du code, nous pourrions transformer cette notice en
			// exception en définissant un `exception handler`, mais ce n'est pas le but recherché. De ce fait,
			// puisque la fonction retourne `null` en cas d'échec, l'utilisation de `@` évite l'émission des notices
			// (warning, etc.).
			try {
				$state = @unserialize($_SESSION[$this->key]);

				if ($state === false) {
					return null;
				}

				return $state;
			}

			// Nous ne catchons pas ici des erreurs liées à la serialisation mais plutôt à celles liées au runtime PHP.
			catch (Throwable) {
				return null;
			}
		}

		return null;
	}
}