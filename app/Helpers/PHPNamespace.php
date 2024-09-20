<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin\Helpers;

use InvalidArgumentException;
use Stringable;
use Syntatis\ComposerProjectPlugin\Traits\ConsoleOutput;
use Syntatis\Utils\Val;

use function is_int;
use function preg_match;
use function strlen;

class PHPNamespace implements Stringable
{
	use ConsoleOutput;

	private string $namespace;

	/**
	 * @param string $namespace    The namespace to validate.
	 * @param string $outputPrefix The prefix to use as the prefix on the Exception message.
	 *                             It may appear on the console output.
	 */
	public function __construct(string $namespace, string $outputPrefix = '')
	{
		$this->consoleOutputPrefix = $outputPrefix;
		$this->namespace = $this->validate($namespace);
	}

	private function validate(string $namespace): string
	{
		if (Val::isBlank($namespace)) {
			throw new InvalidArgumentException(
				$this->prefixed('The PHP namespace cannot be blank.'),
			);
		}

		$match = preg_match('/^([A-Z][a-zA-Z0-9_]*)(\\\[A-Z][a-zA-Z0-9_]*)*$/', $namespace);

		if (is_int($match) && $match <= 0) {
			throw new InvalidArgumentException(
				$this->prefixed('Invalid namespace format.'),
			);
		}

		if (strlen($namespace) > 214) {
			throw new InvalidArgumentException(
				$this->prefixed('The PHP namespace must be less than or equal to 214 characters.'),
			);
		}

		return $namespace;
	}

	public function __toString(): string
	{
		return $this->namespace;
	}
}
