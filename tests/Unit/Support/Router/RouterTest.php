<?php

namespace Tests\Unit\Support\Router;

use App\Support\Request;
use App\Support\Router\Router;
use Tests\TestCase;

class RouterTest extends TestCase
{
	/**
	 * @test
	 */
	public function it_can_handle_a_list_of_routes(): void
	{
		$router = new Router;
		$router->get("/", function (Request $request) {
			return "Hello world!";
		});

		$this->assertCount(1, $router->getRoutes());
		$this->assertEquals("/", $router->getRoutes()[0]->getPath());
	}
}
