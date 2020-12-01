<?php

if (!function_exists("env")) {
	/**
	 * Retourne un élément présent dans la variable globale `$_ENV`. Si l'élément n'existe pas, nous retournerons la
	 * valeur par défaut définie par la fonction.
	 *
	 * @param  string $key
	 * @param  mixed|null $default
	 * @return mixed
	 */
	function env(string $key, mixed $default = null): mixed
	{
		if (isset($_ENV[$key])) {
			return $_ENV[$key];
		}

		return $default;
	}
}