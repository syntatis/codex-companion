<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin\Helpers;

use InvalidArgumentException;
use Stringable;
use Syntatis\ComposerProjectPlugin\Traits\ConsoleOutput;

use function is_int;
use function preg_match;
use function strlen;
use function Syntatis\Utils\is_blank;

class VendorPrefix implements Stringable
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
		if (is_blank($namespace)) {
			throw new InvalidArgumentException(
				$this->prefixed('Vendor prefix cannot be blank.'),
			);
		}

		$match = preg_match('/^([A-Z][a-zA-Z0-9_]*)(\\\[A-Z][a-zA-Z0-9_]*)*$/', $namespace);

		if (is_int($match) && $match <= 0) {
			throw new InvalidArgumentException(
				$this->prefixed('Invalid vendor prefix format.'),
			);
		}

		if (strlen($namespace) > 214) {
			throw new InvalidArgumentException(
				$this->prefixed('The vendor prefix must be less than or equal to 214 characters.'),
			);
		}

		return $namespace;
	}

	public function __toString(): string
	{
		return $this->namespace;
	}
}
