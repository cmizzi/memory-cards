<?php

use App\Http\Controllers;
use App\Http\Exceptions\HttpException;
use App\Http\Exceptions\RouteNotFoundException;
use App\Http\Response;

// Charger les dépendances. En réalité, cela permet d'utiliser la PSR4, permettant d'utiliser les namespaces afin de
// ranger proprement notre code.
require_once __DIR__ . "/" . "../vendor/autoload.php";

// Démarrons les sessions : pour chaque requête, nous souhaitons avoir une session active afin de pouvoir
// stocker/charger le plateau de jeu, ou simplement reconnaître l'utilisateur.
session_start();

// Stockons le chemin utilisé afin de servir la requête : toutes les requêtes sont envoyées à ce fichier. A nous de
// déterminer si le chemin est une requête valide pour notre application. Nous n'avons besoin d'extraire que le chemin,
// pas les arguments passés (`/mon-chemin`).
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// Liste des chemins auquel nous avons une action spécifique. Pour chaque chemin, nous autorisons une méthode et
// associons un contrôleur afin de séparer le code logique.
$routes = [
	"/"           => [ "method" => "GET"  , "action" => [Controllers\WelcomeController::class, "index"    ]] ,
	"/api/scores" => [ "method" => "GET"  , "action" => [Controllers\HighScoresController::class, "index" ]] ,
	"/api/game"   => [ "method" => "POST" , "action" => [Controllers\GameController::class, "store"       ]] ,
];

try {
	// Si la chemin n'existe pas, alors nous ne pouvons pas gérer la requête du client. Indiquons lui que cette requête
	// est une erreur 404.
	if (!isset($routes[$path])) {
		throw new RouteNotFoundException;
	}

	// Stockons la route courante dans une variable pour faciliter l'utilisation.
	$route = $routes[$path];

	// Le chemin existe. Maintenant, vérifions que la méthode correspond. S'il ne correspond pas, lançons de nouveau une
	// exception.
	if ($route["method"] !== $_SERVER["REQUEST_METHOD"]) {
		throw new HttpException("Oops, the method \"{$_SERVER["REQUEST_METHOD"]}\" is not allowed for this route.", 405);
	}

	// Exécutons le contrôleur ainsi que la méthode associée.
	$response = call_user_func([new $route["action"][0], $route["action"][1]]);

	// Si la valeur retournée par le contrôleur n'est pas une réponse valide, alors essayons de la transformer.
	if (!$response instanceof Response) {
		$response = new Response($response, 200);
	}
}

// Une erreur générique a été lancée depuis un contrôleur ou depuis l'implémentation du routage (URL incorrecte).
catch (HttpException $e) {
	$response = new Response($e->getMessage(), $e->status);
}

// Une erreur générique a été lancée.
catch (Throwable $e) {
	$response = new Response($e->getMessage(), 500);
}

// Nous sommes certains à ce point d'avoir une réponse à renvoyer au client.
$response->send();
