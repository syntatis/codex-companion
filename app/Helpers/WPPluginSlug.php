<?php

declare(strict_types=1);

namespace Codex\Companion\Helpers;

use Codex\Companion\Traits\ConsoleOutput;
use InvalidArgumentException;
use Stringable;
use Syntatis\Utils\Str;
use Syntatis\Utils\Val;

use function strlen;

class WPPluginSlug implements Stringable
{
	use ConsoleOutput;

	private string $slug;

	public function __construct(string $slug, string $outputPrefix = '')
	{
		$this->consoleOutputPrefix = $outputPrefix;
		$this->slug = $this->validate($slug);
	}

	/**
	 * Transform and retrieve the plugin name from the slug.
	 *
	 * @return string The plugin name. e.g. "plugin-name" -> "Plugin Name"
	 */
	public function toPluginName(): string
	{
		return Str::toTitleCase($this->slug);
	}

	public function toNamespace(): string
	{
		return Str::toPascalCase($this->slug);
	}

	public function __toString(): string
	{
		return $this->slug;
	}

	private function validate(string $slug): string
	{
		if (Val::isBlank($slug)) {
			throw new InvalidArgumentException(
				$this->prefixed('The plugin slug cannnot be blank.'),
			);
		}

		$slug = Str::toSlug(Str::toKebabCase($slug));

		if (strlen($slug) > 214) {
			throw new InvalidArgumentException(
				$this->prefixed('The plugin slug must be less than or equal to 214 characters.'),
			);
		}

		return $slug;
	}
}
