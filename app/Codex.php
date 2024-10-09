<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion;

use Symfony\Component\Filesystem\Path;
use Syntatis\Codex\Companion\Helpers\ComposerCollection;
use Syntatis\Utils\Arr;
use Syntatis\Utils\Str;
use Syntatis\Utils\Val;

use function in_array;
use function is_array;
use function is_string;

class Codex
{
	private ComposerCollection $composer;

	private string $projectPath;

	public function __construct(string $projectPath)
	{
		$this->composer = new ComposerCollection($projectPath);
		$this->projectPath = $projectPath;
	}

	/**
	 * @param string|null $path The path to append to the project path. The path appended should start
	 *                          with a trailing slash e.g. `/path/to/file.json`.
	 */
	public function getProjectPath(?string $path = null): string
	{
		$projectPath = $this->projectPath ?? '';

		if (! Val::isBlank($path)) {
			$projectPath = $this->projectPath . $path;
		}

		return Path::normalize($projectPath);
	}

	public function getName(): ?string
	{
		$name = $this->composer->get('name');

		return is_string($name) && ! Val::isBlank($name) ? $name : null;
	}

	public function getRootNamespace(): ?string
	{
		$namespace = null;
		$psr4 = $this->composer->get('autoload.psr-4', []);

		if (is_array($psr4) && ! Arr::isList($psr4)) {
			foreach ($psr4 as $key => $value) {
				if (
					(is_string($value) && Str::endsWith($value, 'app/')) ||
					(is_array($value) && in_array('app/', $value, true))
				) {
					$namespace = $key;
					break;
				}
			}
		}

		return $namespace;
	}

	/**
	 * Retrieve the data from the Codex config.
	 *
	 * @param string $key     The key may be the key of collection, or a dot
	 *                        notation to retrieve nested data.
	 * @param mixed  $default The default value to return if the key is not found.
	 *
	 * @return mixed
	 */
	public function getConfig(?string $key = null, $default = null)
	{
		if (Val::isBlank($key)) {
			return $this->composer->get('extra.codex', $default);
		}

		return $this->composer->get('extra.codex.' . $key, $default);
	}

	/**
	 * Retrieve the Composer data collection.
	 */
	public function getComposer(): ComposerCollection
	{
		return $this->composer;
	}
}
