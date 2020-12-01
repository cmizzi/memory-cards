<?php

namespace Tests;

use App\Models\Model;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
	/**
	 * Avant chaque test, on s'assure que la base de données est complète. Dans un cadre idéal, cet étape ne devrait
	 * être effectuée seulement pour les tests utilisant la base de données. Par souci de simplicité, considérons que
	 * tous les tests ont besoin de la base.
	 */
	protected function setUp(): void
	{
		// Attention, d'après le fichier `phpunit.xml`, les environnements deviennent les suivants : nous utilisons pour
		// les tests la base de données `sqlite`, qui permet de stocker en mémoire les tables. Cela nous évite de
		// définir une nouvelle base pour les tests unitaires. Cependant, gardons à l'esprit que les moteurs de base
		// ne sont les mêmes et qu'avec une utilisation manuelle, nous pourrions rentrer dans des cas gérés par MySQL
		// mais pas de la même manière avec SQLite. C'est par exemple le cas sur la création de la table : les mots
		// clefs ne sont pas les mêmes et l'exécution ci-dessous ne fonctionnera pas sur un moteur MySQL.
		//
		// Puisque la base est gérée en mémoire, nous avons besoin d'avoir un schema standard pour toutes les requêtes
		// SQL. Définissons donc le schema général avant chaque test.
		Model::getConnection()->exec(<<<EOSQL
			CREATE TABLE IF NOT EXISTS `scores` (
				`id` INTEGER PRIMARY KEY AUTOINCREMENT,
				`score` INT unsigned,
				`created_at` DATETIME
			);
		EOSQL);

		// N'oublions pas d'appeler la méthode parente, sans quoi, les tests seront tous cassés.
		parent::setUp();
	}
}


