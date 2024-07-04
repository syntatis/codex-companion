<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin\Helpers;

use InvalidArgumentException;
use Stringable;
use Syntatis\ComposerProjectPlugin\Traits\ConsoleOutput;

use function strlen;
use function Syntatis\Utils\is_blank;
use function Syntatis\Utils\kebabcased;
use function Syntatis\Utils\slugify;
use function Syntatis\Utils\titlecased;

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
	 * @return string The plugin name. e.g. "my-plugin" -> "My Plugin"
	 */
	public function toPluginName(): string
	{
		return titlecased($this->slug);
	}

	public function __toString(): string
	{
		return $this->slug;
	}

	private function validate(string $slug): string
	{
		if (is_blank($slug)) {
			throw new InvalidArgumentException(
				$this->prefixed('The plugin slug cannnot be blank.'),
			);
		}

		$slug = slugify(kebabcased($slug));

		if (strlen($slug) > 214) {
			throw new InvalidArgumentException(
				$this->prefixed('The plugin slug must be less than or equal to 214 characters.'),
			);
		}

		return $slug;
	}
}
