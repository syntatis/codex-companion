<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin\Helpers;

use InvalidArgumentException;
use Stringable;
use Syntatis\ComposerProjectPlugin\Traits\ConsoleOutput;
use Syntatis\Utils\Val;

use function strlen;

class WPPluginName implements Stringable
{
	use ConsoleOutput;

	private string $name;

	public function __construct(string $name, string $outputPrefix = '')
	{
		$this->consoleOutputPrefix = $outputPrefix;
		$this->name = $this->validate($name);
	}

	private function validate(string $name): string
	{
		if (Val::isBlank($name)) {
			throw new InvalidArgumentException(
				$this->prefixed('The plugin name cannnot be blank.'),
			);
		}

		if (strlen($name) > 214) {
			throw new InvalidArgumentException(
				$this->prefixed('The plugin name must be less than or equal to 214 characters.'),
			);
		}

		return $name;
	}

	public function __toString(): string
	{
		return $this->name;
	}
}
