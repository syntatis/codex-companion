<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin\Exceptions;

use RuntimeException;
use Throwable;

class SearchReplaceException extends RuntimeException
{
	private string $attemptedFile;

	public function __construct(string $attemptedFile, int $code = 0, ?Throwable $previous = null)
	{
		$this->attemptedFile = $attemptedFile;

		parent::__construct('An error occurred while performing search and replace on a file', $code, $previous);
	}

	public function getAttemptedFile(): string
	{
		return $this->attemptedFile;
	}
}
