<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers;

use InvalidArgumentException;
use Stringable;
use Syntatis\Utils\Val;

use function is_int;
use function preg_match;
use function strlen;

class PHPNamespace implements Stringable
{
	/** @phpstan-var non-empty-string */
	private string $namespace;

	/** @param string $namespace The namespace to validate. */
	public function __construct(string $namespace)
	{
		$this->namespace = $this->validate($namespace);
	}

	/** @phpstan-return non-empty-string */
	private function validate(string $namespace): string
	{
		if (Val::isBlank($namespace)) {
			throw new InvalidArgumentException('The PHP namespace cannot be blank.');
		}

		$match = preg_match('/^([A-Z][a-zA-Z0-9_]*)(\\\[A-Z][a-zA-Z0-9_]*)*$/', $namespace);

		if (is_int($match) && $match <= 0) {
			throw new InvalidArgumentException('Invalid PHP namespace format.');
		}

		if (strlen($namespace) > 214) {
			throw new InvalidArgumentException('The PHP namespace must be less than or equal to 214 characters.');
		}

		return $namespace;
	}

	/** @phpstan-return non-empty-string */
	public function __toString(): string
	{
		return $this->namespace;
	}
}
