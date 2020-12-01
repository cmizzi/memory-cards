<?php

namespace App\Models;

/**
 * @property int $id
 * @property int $score
 * @property string $created_at
 */
class Score extends Model
{
	/**
	 * Sauvegarde le modèle dans la base de données.
	 *
	 * @return bool
	 */
	public function save(): bool
	{
		// Deux choix s'offrent à nous : nous pouvons effectuer une requête à chaque enregistrement afin de vérifier
		// que la table finale existe, ou bien ne pas du tout gérer ce cas et admettre par défaut que la table existe.
		// Ici, je pense que la deuxième solution est la meilleure, puisque la table des scores ne sera jamais modifiée.
		// Effectuons seulement la requête d'insertion.

		// Si le modèle existe déjà (présence du champ `id`), nous n'autorisons pas la mise à jour. Rien ne se produit.
		if (!empty($this->id)) {
			return false;
		}

		// Si la date de création n'a pas été spécifiée, affectons la automatiquement.
		if (empty($this->created_at)) {
			// MySQL format.
			$this->attributes["created_at"] = date('Y-m-d H:i:s');
		}

		// Sinon, nous avons besoin de créer un nouvel enregistrement.
		$statement = static::getConnection()->prepare("INSERT INTO scores (score, created_at) VALUES (?, ?)");

		// Exécutons la requête préparée. Cette dernière utilise des points d'interrogation (?). Nous aurions pu
		// directement injecter les variables dans la requête, mais pour des raisons de sécurité (injection SQL,
		// notamment), il est préférable de toujours utiliser les requêtes préparées, qui vont prévenir de tout problème
		// potentiel. Attention cependant : les requêtes préparées sont obligatoires dès lors qu'un acteur externe
		// (un champ `$_GET`, par exemple) doit être persisté dans la base. Ici, nous savons que les scores sont
		// intégralement gérés par le serveur, il n'y a donc aucun risque d'injection puisque nous dirigeons la donnée.
		// Cependant, il reste une très bonne pratique de prendre l'habitude à toujours utiliser les requêtes préparées.
		//
		// Nous n'avons besoin de ne sauvegarder que le score, puisque la table que nous avons généré auto-génère les
		// champs `id` et `created_at`.
		//
		// La requête finale devient par exemple :
		//
		// ```sql
		// INSERT INTO scores (score, created_at) VALUES (7, "2020-12-01 11:40:20")
		// ```
		$isSuccessful = $statement->execute([$this->score, $this->created_at]);

		// Pour le fun, nous pouvons aussi stocker dans ce modèle l'identifiant qui a été généré par la base de données.
		$this->attributes["id"] = static::getConnection()->lastInsertId();

		return $isSuccessful;
	}

	/**
	 * Retourne une liste de scores, triées par score ascendant (nous souhaitons avoir le score le plus bas en tête).
	 *
	 * @return array
	 */
	static public function get(): array
	{
		// Récupérons les 10 dernières entrées, triées par score ascendant (1 étant en premier et 10 en dernier) et par
		// date ascendant. Le tri par date ascendante est très important : si plusieurs scores sont les mêmes, il faut
		// pouvoir afficher le score qui a été gagné le plus tôt (et non pas le plus récent).
		$statement = static::getConnection()->prepare("SELECT * FROM scores ORDER BY score ASC, created_at ASC LIMIT 10");
		$statement->execute();

		// Nous préférons recevoir un format en tableau pour chacune des entrées retournées par la base de données.
		//
		// ```
		// [
		//    [
		//        "id" => 1,
		//        "score" => 81,
		//        "created_at" => "2020-12-01 09:00:01",
		//    ],
		//    [
		//        "id" => 9,
		//        "score" => 82,
		//        "created_at" => "2020-12-01 10:00:01",
		//    ],
		//    [
		//        "id" => 298,
		//        "score" => 82,
		//        "created_at" => "2020-12-01 11:00:01",
		//    ],
		// ]
		// ```
		return $statement->fetchAll(\PDO::FETCH_ASSOC);
	}
}