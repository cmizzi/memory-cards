<?php

namespace App\Http;

use JetBrains\PhpStorm\Pure;

class Response
{
	/**
	 * @var int
	 */
	private int $status;

	/**
	 * @var string|array
	 */
	private string|array $content;

	/**
	 * @param string|array $content
	 * @param int $status
	 */
	public function __construct(string|array $content = '', int $status = 200)
	{
		$this->status = $status;
		$this->content = $content;
	}

	/**
	 * @return $this
	 */
	public function send(): self
	{
		return $this
			->sendHeaders()
			->sendContent();
	}

	/**
	 * Send the headers to the client.
	 *
	 * @return $this
	 */
	private function sendHeaders(): self
	{
		http_response_code($this->status);

		if ($this->isRedirect()) {
			header("Location: {$this->content}", false, $this->status);
		}

		// If the request comes from Ajax (XMLHttpRequest), we want return a JSON response.
		if ($this->wantsJson()) {
			header("Content-Type: application/json", true, $this->status);
		}

		return $this;
	}

	/**
	 * Write the response body.
	 *
	 * @return $this
	 */
	private function sendContent(): self
	{
		$content = $this->content;

		// If the response is a redirection, we can't produce an body content.
		if ($this->isRedirect()) {
			return $this;
		}

		// If the request comes from Ajax (XMLHttpRequest), we want return a JSON response.
		if ($this->wantsJson()) {
			// If the content is a string (can only be a string or array, based on typed constructor), we have to turn
			// it into an array.
			if (is_string($content)) {
				$content = ["status" => $this->status, "message" => $content];
			}

			// Convert the array into JSON.
			$content = json_encode($content);
		}

		// Output the content.
		echo $content;

		return $this;
	}

	/**
	 * Check if the request is coming from web browser, using XMLHttpRequest.
	 *
	 * @return bool
	 */
	private function wantsJson(): bool
	{
		return ($_SERVER["HTTP_X_REQUESTED_WITH"] ?? null) === "XMLHttpRequest";
	}

	/**
	 * Check if the response is a redirection.
	 *
	 * @return bool
	 */
	#[Pure]
	private function isRedirect(): bool
	{
		return in_array($this->status, [301, 302]);
	}
}