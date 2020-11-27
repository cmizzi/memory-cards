<?php

namespace App\Support\Router;

use App\Support\Router\Exceptions\RouteNotFoundException;

class Router
{
	/**
	 * @var Route[]
	 */
	private array $routes = [];

	/**
	 * @param  string  $path
	 * @param  mixed  $callable
	 * @return $this
	 */
	public function get(string $path, mixed $callable): self
	{
		$this->addRoute("GET", $path, $callable);
		return $this;
	}

	/**
	 * @param  string  $path
	 * @param  mixed  $callable
	 * @return $this
	 */
	public function post(string $path, mixed $callable): self
	{
		$this->addRoute("POST", $path, $callable);
		return $this;
	}

	/**
	 * @param  string  $method
	 * @param  string  $path
	 * @param  mixed  $callable
	 * @return $this
	 */
	private function addRoute(string $method, string $path, mixed $callable): self
	{
		$this->routes[] = new Route($method, $path, $callable);
		return $this;
	}

	/**
	 * @param  string  $path
	 * @return Route
	 * @throws RouteNotFoundException
	 */
	public function getRouteMatching(string $path): Route
	{
		foreach ($this->routes as $route) {
			if (!($route->match($path))) {
				continue;
			}

			return $route;
		}

		throw new RouteNotFoundException($path);
	}

	/**
	 * @return Route[]
	 */
	public function getRoutes(): array
	{
		return $this->routes;
	}
}