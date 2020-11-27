<?php

namespace Tests\Unit;

use App\Game;
use App\GameStorage\InMemoryStorage;
use App\Exceptions\GameActionNotFound;
use JetBrains\PhpStorm\Pure;
use Tests\TestCase;

class GameTest extends TestCase
{
	/**
	 * @test
	 */
	public function throw_exception_when_the_game_action_is_invalid(): void
	{
		$this->expectException(GameActionNotFound::class);

		$game = $this->createGameInstance();
		$game->run("non-existing-action");
	}

	/**
	 * @test
	 * @throws GameActionNotFound
	 */
	public function it_can_reveal_a_card(): void
	{
		$game = $this->createGameInstance();
		$game->run("reveal", 4);

		$this->assertTrue($game->getState()->getBoard()[4]["reveal"]);
	}

	/**
	 * @test
	 */
	public function it_can_reset_the_game(): void
	{
		$game = $this->createGameInstance();
		$game->run("reveal", 4);
		$game->run("reset");

		$this->assertFalse($game->getState()->getBoard()[4]["reveal"]);
	}

	/**
	 * @return Game
	 */
	#[Pure] private function createGameInstance(): Game
	{
		return new Game(new InMemoryStorage);
	}
}