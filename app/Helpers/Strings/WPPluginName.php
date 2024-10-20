<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers\Strings;

use InvalidArgumentException;
use Stringable;
use Syntatis\Utils\Val;

use function strlen;

class WPPluginName implements Stringable
{
	/** @phpstan-var non-empty-string */
	private string $name;

	public function __construct(string $name)
	{
		$this->name = $this->validate($name);
	}

	/** @phpstan-return non-empty-string */
	private function validate(string $name): string
	{
		if (Val::isBlank($name)) {
			throw new InvalidArgumentException('The plugin name cannnot be blank.');
		}

		if (strlen($name) > 214) {
			throw new InvalidArgumentException('The plugin name must be less than or equal to 214 characters.');
		}

		return $name;
	}

	/** @phpstan-return non-empty-string */
	public function __toString(): string
	{
		return $this->name;
	}
}
