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
	private string $slug;

	public function __construct(string $slug)
	{
		$this->slug = $this->validate($slug);
	}

	public function __toString(): string
	{
		return $this->slug;
	}

	private function validate(string $slug): string
	{
		if (Val::isBlank($slug)) {
			throw new InvalidArgumentException('The plugin slug cannnot be blank.');
		}

		$inflector = InflectorFactory::create()->build();
		$slug = $inflector->urlize(Str::toKebabCase($slug));

		if (strlen($slug) > 214) {
			throw new InvalidArgumentException('The plugin slug must be less than or equal to 214 characters.');
		}

		return $slug;
	}
}
