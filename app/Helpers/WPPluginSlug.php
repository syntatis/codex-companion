<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers;

use Doctrine\Inflector\InflectorFactory;
use InvalidArgumentException;
use Stringable;
use Syntatis\Utils\Str;
use Syntatis\Utils\Val;

use function strlen;

class WPPluginSlug implements Stringable
{
	/** @phpstan-var non-empty-string */
	private string $slug;

	public function __construct(string $slug)
	{
		$this->slug = $this->validate($slug);
	}

	/** @phpstan-return non-empty-string */
	public function __toString(): string
	{
		return $this->slug;
	}

	/** @phpstan-return non-empty-string */
	private function validate(string $slug): string
	{
		$inflector = InflectorFactory::create()->build();
		$slug = $inflector->urlize(Str::toKebabCase($slug));

		if (Val::isBlank($slug)) {
			throw new InvalidArgumentException('The plugin slug cannot be blank.');
		}

		if (strlen($slug) > 214) {
			throw new InvalidArgumentException('The plugin slug must be less than or equal to 214 characters.');
		}

		return $slug;
	}
}
