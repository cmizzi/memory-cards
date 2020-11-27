<?php

namespace App;

use App\Exceptions\InvalidBoardException;

class GameState
{
	/**
	 * This represents the maximum number of different tiles.
	 *
	 * @var int
	 */
	private const MAX_DISTINCT_CARDS = 18;

	/**
	 * Represent the game board.
	 *
	 * @var array
	 */
	private array $board;

	/**
	 * @var bool
	 */
	private bool $winner = false;

	/**
	 * @var bool
	 */
	private bool $partyOver = false;

	/**
	 * Card type which is pending for a suit.
	 *
	 * @var ?int
	 */
	private ?int $pendingSuit = null;

	/**
	 * The current card type user played.
	 *
	 * @var int |null
	 */
	private ?int $currentCard = null;

	/**
	 * When initializing, we need to generate a board.
	 *
	 * @param int $distinctCards
	 * @param int $sharedType
	 * @throws InvalidBoardException
	 */
	public function __construct(int $distinctCards = 18, int $sharedType = 2)
	{
		if ($distinctCards > static::MAX_DISTINCT_CARDS) {
			throw new InvalidBoardException(
				vsprintf("Cannot create more than %s distinct cards", [static::MAX_DISTINCT_CARDS])
			);
		}
		// Split the number of cards by 2, in order to get 2 identical cards for the maximum number of cards allowed.
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
		$cards = range(0, $distinctCards);

		// For each card, we'll set the ID of the card and set the reveal state to false.
		//
		// [
		//    0  => ["card" => 0  , "reveal" => false],
		//    1  => ["card" => 1  , "reveal" => false],
		//    2  => ["card" => 2  , "reveal" => false],
		//    3  => ["card" => 3  , "reveal" => false],
		//    4  => ["card" => 4  , "reveal" => false],
		//    5  => ["card" => 5  , "reveal" => false],
		//    ...
		//    18 => ["card" => 18 , "reveal" => false],
		//    19 => ["card" => 0  , "reveal" => false],
		//    20 => ["card" => 1  , "reveal" => false],
		//    21 => ["card" => 2  , "reveal" => false],
		//    ...
		//    35 => ["card" => 17 , "reveal" => false],
		//    36 => ["card" => 18 , "reveal" => false],
		// ]
		$sample = array_map(fn ($index) => ["card" => $index, "reveal" => false], $cards);
		$cards  = [];

		// Now, we can duplicate the card to have exactly the amount as `CARDS_PER_BOARD`.
		foreach (range(0, $sharedType) as $_) {
			$cards = [...$cards, ...$sample];
		}

		// Randomize elements within the cards deck. At this point, the cards variable will be something like this :
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

		// Assign the cards deck as board.
		$this->board = $cards;
	}

	/**
	 * Reveal a card.
	 *
	 * @param  int $cardIndex
	 * @return GameState
	 */
	public function reveal(int $cardIndex): self
	{
		$currentCard = $this->board[$cardIndex];
		$this->currentCard = $currentCard["card"];

		// If there's no pending card, simply push the action and reveal the card.
		if ($this->pendingSuit === null) {
			$this->pendingSuit = $currentCard["card"];
			$this->board[$cardIndex]["reveal"] = true;

			return $this;
		}

		// There's a pending card. Check if the current card is the same type as the pending one.
		if ($this->pendingSuit === $currentCard["card"]) {
			// The card type is equal. Reveal the card and check if the game is over.
			$this->board[$cardIndex]["reveal"] = true;

			// Before resetting the pending card, we have to check that all cards has been found. This is useless when
			// working with only a pair (2 cards), but with 3, it prevents an edge case.
			if (empty($this->getRemainingCards($this->pendingSuit))) {
				$this->pendingSuit = true;
			}

			// Check if there's remaining cards to play with. If not, the game is over.
			if (empty($this->getRemainingCards())) {
				$this->winner = true;
				$this->partyOver = true;
			}

			return $this;
		}

		// No match found, hide the pending cards.
		foreach ($this->board as $key => $card) {
			// Check if the card match the pending card type. If not, simply continue.
			if ($card["card"] !== $this->pendingSuit) {
				continue;
			}

			$this->board[$key]["reveal"] = false;
		}

		// Reset the pending card for the next round.
		$this->pendingSuit = null;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isWinner(): bool
	{
		return $this->winner;
	}

	/**
	 * @return bool
	 */
	public function isPartyOver(): bool
	{
		return $this->partyOver;
	}

	/**
	 * Get the board. This method will filter the type of card, when the card is not reveal in order to prevent the
	 * client to cheat and read the JS memory and grab each card type.
	 *
	 * @return array
	 */
	public function getBoard(): array
	{
		return array_map(fn ($card) => ["reveal" => $card["reveal"]], $this->board);
	}

	/**
	 * Get the card type the user played.
	 *
	 * @return int|null
	 */
	public function getCurrentCard(): ?int
	{
		return $this->currentCard;
	}

	/**
	 * Reset all cards.
	 *
	 * @return $this
	 */
	public function reset(): self
	{
		// Reset the pending card and user card.
		$this->pendingSuit = null;
		$this->currentCard = null;

		// Reset all cards from the board.
		foreach (array_keys($this->board) as $key) {
			$this->board[$key]["reveal"] = false;
		}

		return $this;
	}

	/**
	 * Get remaining cards.
	 *
	 * @param  ?int $type
	 * @return array
	 */
	private function getRemainingCards(?int $type = null): array
	{
		$pendingCards = [];

		foreach ($this->board as $key => $card) {
			// We only want cards of the type and which are not already reveal.
			if (($type !== null && $card["card"] !== $type) || $card["reveal"]) {
				continue;
			}

			$pendingCards[] = $key;
		}

		return $pendingCards;
	}

	/**
	 * Generate a hash based on the current board state.
	 *
	 * @return string
	 */
	public function getBoardHash(): string
	{
		return sha1(json_encode($this->board));
	}

	/**
	 * Override the current board. This method must only be called during tests.
	 *
	 * @param array $board
	 * @return $this
	 */
	public function overrideBoard(array $board): self
	{
		$this->board = $board;

		return $this;
	}
}