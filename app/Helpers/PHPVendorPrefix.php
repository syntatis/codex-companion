<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers;

use InvalidArgumentException;
use Stringable;
use Syntatis\Utils\Val;

use function is_int;
use function preg_match;
use function strlen;

class PHPVendorPrefix implements Stringable
{
	private string $namespace;

	/** @param string $namespace The namespace to validate. */
	public function __construct(string $namespace)
	{
		$this->namespace = $this->validate($namespace);
	}

	private function validate(string $namespace): string
	{
		if (Val::isBlank($namespace)) {
			throw new InvalidArgumentException('The PHP vendor prefix cannot be blank.');
		}

		$match = preg_match('/^([A-Z][a-zA-Z0-9_]*)(\\\[A-Z][a-zA-Z0-9_]*)*$/', $namespace);

		if (is_int($match) && $match <= 0) {
			throw new InvalidArgumentException('Invalid PHP vendor prefix format.');
		}

		if (strlen($namespace) > 214) {
			throw new InvalidArgumentException('The PHP vendor prefix must be less than or equal to 214 characters.');
		}

		return $namespace;
	}

	public function __toString(): string
	{
		return $this->namespace;
	}
}
