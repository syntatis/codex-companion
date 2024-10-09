<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Exceptions;

use Exception;

use function sprintf;

class MissingRequiredFile extends Exception
{
	public function __construct(string $filePath)
	{
		parent::__construct(sprintf('Missing required file: %s', $filePath));
	}
}
