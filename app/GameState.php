<?php

namespace App;

class GameState
{
	/**
	 * Représente le nombre maximale de carte unique sur le plateau.
	 *
	 * @var int
	 */
	private const MAX_DISTINCT_CARDS = 9;

	/**
	 * Représente l'état courant du jeu (le plateau).
	 *
	 * @var array
	 */
	private array $board;

	/**
	 * Est-ce aue le joueur a gagné ?
	 *
	 * @var bool
	 */
	private bool $winner = false;

	/**
	 * Est-ce que la partie est terminée ?
	 *
	 * @var bool
	 */
	private bool $partyOver = false;

	/**
	 * Type de carte en attente dans la suite.
	 *
	 * @var ?int
	 */
	private ?int $pendingSuit = null;

	/**
	 * La carte actuellement jouée par le joueur.
	 *
	 * @var int |null
	 */
	private ?int $currentCard = null;

	/**
	 * Est-ce qu'une suite a été ré-initialisée ?
	 *
	 * @var bool
	 */
	private bool $hasFailed = false;

	/**
	 * Initialiser un nouveau plateau.
	 */
	public function __construct()
	{
		// Coupons le jeu en deux : nous avons 18 cartes disponibles. Générons un tableau de 18 entrées.
		//
		// [
		//    0  => 0,
		//    1  => 1,
		//    2  => 2,
		//    3  => 3,
		//    4  => 4,
		//    5  => 5,
		//    ...
		//    17 => 17,
		//    18 => 18,
		// ]
		$cards = range(0, static::MAX_DISTINCT_CARDS - 1);

		// Pour chaque entrée (qui représente un type unique de carte), nous allons modifier le tableau afin d'y ajouter
		// des informations complémentaires : un identifiant de carte (`type`) et un champ permettant de savoir si la
		// carte a été retournée ou non (`reveal`).
		//
		// [
		//    0  => ["type" => 0  , "reveal" => false],
		//    1  => ["type" => 1  , "reveal" => false],
		//    2  => ["type" => 2  , "reveal" => false],
		//    3  => ["type" => 3  , "reveal" => false],
		//    4  => ["type" => 4  , "reveal" => false],
		//    5  => ["type" => 5  , "reveal" => false],
		//    ...
		//    17 => ["type" => 17 , "reveal" => false],
		//    18 => ["type" => 18 , "reveal" => false],
		// ]
		$sample = array_map(fn ($index) => ["type" => $index, "reveal" => false], $cards);

		// Maintenant que nous avons un total de 18 cartes uniques, doublons le tableau avec les mêmes informations.
		//
		// [
		//    0  => ["type" => 0  , "reveal" => false],
		//    1  => ["type" => 1  , "reveal" => false],
		//    2  => ["type" => 2  , "reveal" => false],
		//    3  => ["type" => 3  , "reveal" => false],
		//    4  => ["type" => 4  , "reveal" => false],
		//    5  => ["type" => 5  , "reveal" => false],
		//    ...
		//    18 => ["type" => 18 , "reveal" => false],
		//    19 => ["type" => 0  , "reveal" => false],
		//    20 => ["type" => 1  , "reveal" => false],
		//    21 => ["type" => 2  , "reveal" => false],
		//    ...
		//    35 => ["type" => 17 , "reveal" => false],
		//    36 => ["type" => 18 , "reveal" => false],
		// ]
		$cards = [...$sample, ...$sample];

		// Puisque nous générons un nouveau plateau, nous devons trier chaque entrée de manière aléatoire : nous ne
		// voulons pas que toutes les suites soient les unes à côté des autres.
		//
		// [
		//    0  => ["card" => 8  , "reveal" => false],
		//    1  => ["card" => 1  , "reveal" => false],
		//    2  => ["card" => 0  , "reveal" => false],
		//    3  => ["card" => 17 , "reveal" => false],
		//    4  => ["card" => 12 , "reveal" => false],
		//    5  => ["card" => 4  , "reveal" => false],
		//    ...
		//    18 => ["card" => 18 , "reveal" => false],
		//    19 => ["card" => 0  , "reveal" => false],
		//    20 => ["card" => 1  , "reveal" => false],
		//    21 => ["card" => 2  , "reveal" => false],
		//    ...
		//    35 => ["card" => 13 , "reveal" => false],
		//    36 => ["card" => 15 , "reveal" => false],
		// ]
		shuffle($cards);

		// Stockons le plateau généré afin de pouvoir le manipuler par les actions.
		$this->board = $cards;
	}

	/**
	 * Action qui permet de révéler une carte.
	 *
	 * @param  int $cardIndex
	 * @return GameState
	 */
	public function reveal(int $cardIndex): self
	{
		// Cette action nécessite un index de carte (l'index d'un élément dans le plateau). Récupérons cette carte et
		// définissons là comme la carte courante.
		$currentCard = $this->board[$cardIndex];
		$this->currentCard = $currentCard["type"];

		// Considérons qu'il n'y pas eu d'erreur de la part de l'utilisateur ici.
		$this->hasFailed = false;

		// S'il n'y pas de suite en attente, nous pouvons directement révélé le type de la carte courante.
		if ($this->pendingSuit === null) {
			$this->pendingSuit = $currentCard["type"];
			$this->board[$cardIndex]["reveal"] = true;

			return $this;
		}

		// Si une suite est en cours, nous devons vérifier que la carte courante correspond au même type que celle de la
		// suite.
		if ($this->pendingSuit === $currentCard["type"]) {
			// Les cartes semblent être du même type : nous pouvons retourner la carte en cours.
			$this->board[$cardIndex]["reveal"] = true;

			// Avant de réinitialiser la suite en cours, vérifions tout de même qu'il ne reste pas de carte sur le
			// plateau du même type qui ne sont pas retournées. Si la suite a été trouvée (plus de carte restante),
			// alors nous pouvons ré-initialiser la suite courante.
			if (empty($this->getRemainingCards($this->pendingSuit))) {
				$this->pendingSuit = null;
			}

			// Maintenant, regardons s'il reste des cartes à retourner. S'il n'y en a aucune, alors le jeu est terminée
			// et le joueur a gagné.
			if (empty($this->getRemainingCards())) {
				$this->winner = true;
				$this->partyOver = true;
			}

			return $this;
		}

		// Dans ce cas précis, il y a une suite en cours mais le type de la carte courante ne correspond pas à celui de
		// la suite. Cherchons toutes les cartes du type de la suite en cours et retournons les face cachée.
		foreach ($this->board as $key => $card) {
			// Check if the card match the pending card type. If not, simply continue.
			if ($card["type"] !== $this->pendingSuit) {
				continue;
			}

			$this->board[$key]["reveal"] = false;
		}

		// On peut ré-initialiser la suite.
		$this->pendingSuit = null;

		// Oops, étant donné que la suite a été ré-initialisée, on considère que l'utilisateur a commis une erreur.
		$this->hasFailed = true;

		return $this;
	}

	/**
	 * Est-ce que le joueur a gagné ?
	 *
	 * @return bool
	 */
	public function isWinner(): bool
	{
		return $this->winner;
	}

	/**
	 * Est-ce que la partie est terminée ?
	 *
	 * @return bool
	 */
	public function isPartyOver(): bool
	{
		return $this->partyOver;
	}

	/**
	 * Retourne le plateau. Cependant, les informations sur le type des cartes est filtré à partir du moment que la
	 * carte n'est pas déjà révélé (sinon, un serveur autoritaire ne servirait pas à grand chose si les cartes peuvent
	 * être lues par le client, même si elles sont face cachée).
	 *
	 * @return array
	 */
	public function getBoard(): array
	{
		$board = $this->board;

		foreach ($board as $key => $card) {
			// Si la carte est déjà retournée, nous n'avons aucune action à effectuer.
			if ($card["reveal"]) {
				continue;
			}

			// Si la carte est face cachée, nous ne voulons pas que le client connaisse le type de la carte.
			$board[$key]["type"] = null;
		}

		return $board;
	}

	/**
	 * Retourne le type de la carte jouée par l'utilisateur.
	 *
	 * @return int|null
	 */
	public function getCurrentCard(): ?int
	{
		return $this->currentCard;
	}

	/**
	 * Action qui redémarre le jeu. Recréons une instance du jeu.
	 *
	 * @return $this
	 */
	public function reset(): self
	{
		return new static;
	}

	/**
	 * Retourne le nombre de carte encore retournées face cachée sur le plateau. Cette fonction prend un paramètre
	 * optionnel : le type de la carte à rechercher. Si ce paramètre est spécifié, alors nous n'allons rechercher que
	 * les cartes face cachée pour un type donné. Si le paramètre est vide, alors nous allons vérifier sur tout le
	 * plateau (peu importe le type) si des cartes sont encore face cachée.
	 *
	 * @param  ?int $type
	 * @return array
	 */
	private function getRemainingCards(?int $type = null): array
	{
		$pendingCards = [];

		foreach ($this->board as $key => $card) {
			// On ne souhaite pas les cartes qui ne correspondent pas au type `$type` si l'argument est spécifié ou bien
			// toutes les cartes déjà visibles.
			if (($type !== null && $card["type"] !== $type) || $card["reveal"]) {
				continue;
			}

			$pendingCards[] = $key;
		}

		return $pendingCards;
	}

	/**
	 * Returne vrai si l'action a ré-initialiser une suite.
	 *
	 * @return bool
	 */
	public function hasFailed(): bool
	{
		return $this->hasFailed;
	}

	/**
	 * Génère une chaîne de carte unique par rapport à l'état du jeu actuel.
	 *
	 * @return string
	 */
	public function getBoardHash(): string
	{
		return sha1(json_encode($this->board));
	}

	/**
	 * Permet de modifier le tableau de jeu à des fins de test. Cette méthode ne doit en aucun cas être accessible au
	 * client final.
	 *
	 * @param  array $board
	 * @return $this
	 */
	public function overrideBoard(array $board): self
	{
		$this->board = $board;

		return $this;
	}
}