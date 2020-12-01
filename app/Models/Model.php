<?php

namespace App\Models;

use JsonSerializable;
use PDO;

abstract class Model implements JsonSerializable
{
	/**
	 * Une variable statique est définie durant toute la requête courante. Tous les appels à cette variable retourneront
	 * le même résultat.
	 *
	 * @var PDO|null
	 */
	static protected ?PDO $connection = null;

	/**
	 * Liste des attributs chargés en mémoire.
	 *
	 * @var array
	 */
	protected array $attributes = [];

	/**
	 * @param array $attributes
	 */
	public function __construct(array $attributes = [])
	{
		$this->attributes = $attributes;
	}

	/**
	 * Retourne une connection active à la base de données.
	 *
	 * @return PDO
	 */
	static public function getConnection(): PDO
	{
		// Si la connexion à la base de données n'a pas été établi, faisons le dès le premier appel à cette méthode.
		if (static::$connection === null) {
			static::$connection = new PDO(static::compileDsn(), env("DB_USERNAME"), env("DB_PASSWORD"));
		}

		return static::$connection;
	}

	/**
	 * Générons le DSN nécessaire à la fonction PDO.
	 *
	 * @return string
	 */
	static private function compileDsn(): string
	{
		// Cas spécifique pour `sqlite` : le DSN n'est pas formaté de la même manière : `sqlite:FILE_TO_USE`. Si nous
		// utilisons le format générique (MySQL, PostgreSQL), nous aurons alors un fichier généré avec le DSN (pas top).
		if (env("DB_CONNECTION") === "sqlite") {
			return sprintf("sqlite:%s", env("DB_HOST"));
		}

		return vsprintf("%s:host=%s;port=%s;dbname=%s", [
			env("DB_CONNECTION", "mysql"),
			env("DB_HOST"),
			env("DB_PORT", 3306),
			env("DB_DATABASE"),
		]);
	}

	/**
	 * Pour nos besoins, nous aurions pu directement implémenter la méthode ici. Cependant, afin de simplifier les
	 * scores, j'ai décidé d'implémenter la méthode directement dans le modèle concerné.
	 *
	 * @return bool
	 */
	abstract public function save(): bool;

	/**
	 * Méthode magique en PHP. Celle-ci nous permet d'écrire plus facilement des accès vers des attributs privés, comme
	 * dans l'exemple suivant :
	 *
	 * ```php
	 * $score = new Score;
	 * echo $score->created_at;
	 * ```
	 *
	 * @param  string $key
	 * @return mixed
	 */
	public function __get(string $key): mixed
	{
		// Si l'attribut `$key` existe dans notre liste, alors nous pouvons le retourner. Sinon, par défaut, `null`
		// sera renvoyé.
		if (isset($this->attributes[$key])) {
			return $this->attributes[$key];
		}
	}

	/**
	 * Lorsque l'on appelle la fonction `json_encode` sur cette méthode, nous pouvons retourner un tableau contenant
	 * les attributs.
	 *
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return $this->attributes;
	}
}