<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use InvalidArgumentException;
use Stringable;
use Symfony\Component\String\Inflector\EnglishInflector;
use Syntatis\Codex\Companion\Traits\ConsoleOutput;
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

		$inflector = InflectorFactory::create()->build();
		$slug = $inflector->urlize(Str::toKebabCase($slug));

		if (strlen($slug) > 214) {
			throw new InvalidArgumentException(
				$this->prefixed('The plugin slug must be less than or equal to 214 characters.'),
			);
		}

		return $slug;
	}
}
