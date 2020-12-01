<?php

namespace Tests\Unit;

use App\GameState;
use App\Models\Score;
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
			0 => ["type" => 1, "reveal" => false],
			1 => ["type" => 1, "reveal" => false],
			2 => ["type" => 2, "reveal" => false],
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
			0 => ["type" => 1, "reveal" => false],
			1 => ["type" => 1, "reveal" => false],
			2 => ["type" => 1, "reveal" => false],
			3 => ["type" => 2, "reveal" => false],
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
	public function board_can_be_fully_solved(): void
	{
	    $state = new GameState;

	    // Let's cheat again. Let's play with only 2 cards of the same type.
		$state->overrideBoard([
			0 => ["type" => 1, "reveal" => false],
			1 => ["type" => 1, "reveal" => false],
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
			0 => ["type" => 1, "reveal" => false],
			1 => ["type" => 2, "reveal" => false],
			2 => ["type" => 3, "reveal" => false],
		]);

		$state->reveal(0);
	    $this->assertEquals(1, $state->getCurrentCard());

		$state->reveal(1);
		$this->assertEquals(2, $state->getCurrentCard());

		$state->reveal(2);
		$this->assertEquals(3, $state->getCurrentCard());
	}

	/**
	 * @test
	 */
	public function it_saves_the_score_into_the_database_when_the_player_wins(): void
	{
		$state = new GameState;

		// Override the board to make this test easier.
		$state->overrideBoard([
			0 => ["type" => 1, "reveal" => false],
			1 => ["type" => 1, "reveal" => false],
		]);

		$state->reveal(0);
		$state->reveal(1);

		$scores = Score::get();

		$this->assertTrue($state->isWinner());
		$this->assertCount(1, $scores);
		$this->assertEquals($state->getScore()->score, $scores[0]->score);
	}

	/**
	 * @test
	 */
	public function it_stop_the_party_when_the_time_exceed(): void
	{
		$state = new GameState;

		// On force le temps de démarrage à la limite autorisée - 10 secondes, afin d'être certain que le temps est
		// expiré.
		$state->setStartedAt(time() - GameState::MAX_SCORE - 10);
		$state->reveal(12);

		$this->assertTrue($state->isPartyOver());
		$this->assertFalse($state->isWinner());
		$this->assertNull($state->getScore());
	}
}