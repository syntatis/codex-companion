<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Exceptions;

use Exception;

use function implode;
use function sprintf;

class MissingRequiredInfo extends Exception
{
	public function __construct(string ...$dataIds)
	{
		parent::__construct(sprintf('Missing required info: %s', implode(', ', $dataIds)));
	}
}
