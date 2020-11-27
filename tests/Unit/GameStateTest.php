<?php

namespace Tests\Unit;

use App\Exceptions\InvalidBoardException;
use App\GameState;
use Tests\TestCase;

class GameStateTest extends TestCase
{
	/**
	 * @test
	 */
	public function it_can_reveal_a_card(): void
	{
		$state = new GameState;
		$state->reveal(12);

		$this->assertTrue($state->getBoard()[12]["reveal"]);
	}

	/**
	 * @test
	 */
	public function it_resets_pair_when_the_second_card_is_not_found(): void
	{
		$state = new GameState;

		// Simple cheat :)
		$state->overrideBoard([
			0 => ["card" => 1, "reveal" => false],
			1 => ["card" => 1, "reveal" => false],
			2 => ["card" => 2, "reveal" => false],
		]);

		// Reveal the first card (there's no pending card).
		$state->reveal(0);

		// Assert the card has been reveal.
		$this->assertTrue($state->getBoard()[0]["reveal"]);

		// Reveal a second card of a different type (with a pending card).
		$state->reveal(2);

		// Both must hide from the board.
		$this->assertFalse($state->getBoard()[0]["reveal"]);
		$this->assertFalse($state->getBoard()[2]["reveal"]);
	}

	/**
	 * @test
	 */
	public function it_resets_cards_when_the_type_is_different(): void
	{
	    // This is a simple test when we're not working with pair but with 3 cards of each type.
		$state = new GameState();

		// Simple cheat :)
		$state->overrideBoard([
			0 => ["card" => 1, "reveal" => false],
			1 => ["card" => 1, "reveal" => false],
			2 => ["card" => 1, "reveal" => false],
			3 => ["card" => 2, "reveal" => false],
		]);

		// Reveal the first card (there's no pending card).
		$state->reveal(0);
		$state->reveal(1);

		// Assert the card has been reveal.
		$this->assertTrue($state->getBoard()[0]["reveal"]);
		$this->assertTrue($state->getBoard()[1]["reveal"]);

		// Reveal a second card of a different type (with a pending card).
		$state->reveal(3);

		// Both must hide from the board.
		$this->assertFalse($state->getBoard()[0]["reveal"]);
		$this->assertFalse($state->getBoard()[1]["reveal"]);
		$this->assertFalse($state->getBoard()[3]["reveal"]);
	}

	/**
	 * @test
	 */
	public function it_generate_a_random_board(): void
	{
		$stateA = new GameState;
		$stateB = new GameState;

		// As we don't have access external from the real board, we can simply retrieve a hash based on the current
		// board, without any action applied.
		$this->assertNotEquals($stateA->getBoardHash(), $stateB->getBoardHash());
	}

	/**
	 * @test
	 */
	public function it_throws_an_exception_when_the_number_of_distinct_cards_exceed_the_maximum_number_of_available_tiles(): void
	{
		$this->expectException(InvalidBoardException::class);

		new GameState(22);
	}

	/**
	 * @test
	 */
	public function board_can_be_fully_solved(): void
	{
	    $state = new GameState;

	    // Let's cheat again. Let's play with only 2 cards of the same type.
		$state->overrideBoard([
			0 => ["card" => 1, "reveal" => false],
			1 => ["card" => 1, "reveal" => false],
		]);

		// Reveal our cards.
		$state->reveal(0);
		$state->reveal(1);

		$this->assertTrue($state->getBoard()[0]["reveal"]);
		$this->assertTrue($state->getBoard()[1]["reveal"]);
		$this->assertTrue($state->isWinner());
	}

	/**
	 * @test
	 */
	public function it_stores_the_latest_card_type_the_user_played(): void
	{
	    $state = new GameState;

	    // Override the board to make this test easier.
		$state->overrideBoard([
			0 => ["card" => 1, "reveal" => false],
			1 => ["card" => 2, "reveal" => false],
			2 => ["card" => 3, "reveal" => false],
		]);

		$state->reveal(0);
	    $this->assertEquals(1, $state->getCurrentCard());

		$state->reveal(1);
		$this->assertEquals(2, $state->getCurrentCard());

		$state->reveal(2);
		$this->assertEquals(3, $state->getCurrentCard());
	}
}