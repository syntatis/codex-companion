<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers\Strings;

use InvalidArgumentException;
use Stringable;
use Syntatis\Utils\Val;

use function strlen;

class WPPluginDescription implements Stringable
{
	private string $description;

	public function __construct(string $description)
	{
		$this->description = $this->validate($description);
	}

	private function validate(string $description): string
	{
		if (Val::isBlank($description)) {
			throw new InvalidArgumentException('The plugin description cannnot be blank.');
		}

		if (strlen($description) > 140) {
			throw new InvalidArgumentException('The plugin description must be less than or equal to 140 characters.');
		}

		return $description;
	}

	public function __toString(): string
	{
		return $this->description;
	}
}
