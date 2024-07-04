<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin\Helpers;

use InvalidArgumentException;
use Stringable;
use Syntatis\ComposerProjectPlugin\Traits\ConsoleOutput;

use function array_map;
use function explode;
use function implode;
use function is_int;
use function preg_match;
use function sprintf;
use function strlen;
use function strtolower;
use function Syntatis\Utils\is_blank;

class ProjectName implements Stringable
{
	use ConsoleOutput;

	private string $name;

	public function __construct(string $name, string $outputPrefix = '')
	{
		$this->consoleOutputPrefix = $outputPrefix;
		$this->name = $this->validate($name);
	}

	/** @return string The package slug derived from the project name in kebab case e.g. acme */
	public function getVendorName(): string
	{
		return explode('/', $this->name)[0];
	}

	/** @return string The package name derived from the project name in kebab case e.g. plugin-name */
	public function getPackageName(): string
	{
		return explode('/', $this->name)[1];
	}

	/** @return string The plugin PHP namespace derived from the project name in pascal case e.g. PluginName */
	public function toNamespace(): string
	{
		return implode('\\', array_map('\Syntatis\Utils\pascalcased', explode('/', $this->name)));
	}

	public function __toString(): string
	{
		return $this->name;
	}

	private function validate(string $name): string
	{
		if (is_blank($name)) {
			throw new InvalidArgumentException(
				$this->prefixed('The project name cannnot be blank.'),
			);
		}

		$name = strtolower($name);
		$match = preg_match('/^[a-z0-9]([_.-]?[a-z0-9]+)*\/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$/', $name, $matches);

		if (is_int($match) && $match <= 0) {
			throw new InvalidArgumentException(
				$this->prefixed(
					sprintf('The project name must follow the format "vendor/package", e.g. "acme/plugin-name". "%s" given.', $name),
				),
			);
		}

		if (strlen($name) > 214) {
			throw new InvalidArgumentException(
				$this->prefixed(
					'The project name must be less than or equal to 214 characters.',
				),
			);
		}

		return $name;
	}
}
