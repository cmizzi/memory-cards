<?php

namespace Tests\Unit\Support;

use App\Support\Request;
use Tests\TestCase;

class RequestTest extends TestCase
{
	/**
	 * @test
	 */
	public function it_can_check_if_an_attribute_exists(): void
	{
		$request = new Request("/", ["testcase" => "present"]);

		$this->assertTrue($request->has("testcase"));
	}

	/**
	 * @test
	 */
	public function it_can_return_a_specific_attribute_value(): void
	{
		$request = new Request("/", ["testcase" => "present"]);

		$this->assertTrue($request->has("testcase"));
		$this->assertEquals("present", $request->get("testcase"));
	}
}