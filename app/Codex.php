<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion;

use Adbar\Dot;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Syntatis\Codex\Companion\Helpers\ComposerCollection;
use Syntatis\Utils\Arr;
use Syntatis\Utils\Str;
use Syntatis\Utils\Val;

use function in_array;
use function is_array;
use function is_string;
use function trim;

class Codex
{
	/**
	 * The directory to determine the root namespace.
	 */
	private const ROOT_NAMESPACE_DIR = 'app/';

	private const DEFAULT_SCOPER_OUTPUT_PATH = '/dist/autoload';

	private ComposerCollection $composer;

	/** @var Dot<string,mixed> */
	private Dot $configs;

	private string $projectPath;

	public function __construct(string $projectPath)
	{
		$this->projectPath = $projectPath;
		$this->composer = new ComposerCollection($this->projectPath);
		$this->resolveConfigs();
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

	public function getProjectName(): ?string
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
					(is_string($value) && $value === self::ROOT_NAMESPACE_DIR) ||
					(is_array($value) && in_array(self::ROOT_NAMESPACE_DIR, $value, true))
				) {
					$namespace = $key;
					break;
				}
			}
		}

		return $namespace;
	}

	/**
	 * Retrieve the options from the Codex config.
	 *
	 * @param string $key The key may be the key of collection, or a dot
	 *                    notation to retrieve nested data.
	 *
	 * @return mixed
	 */
	public function getConfig(?string $key = null)
	{
		return $this->configs->get($key);
	}

	/**
	 * Retrieve the Composer data collection.
	 *
	 * @return mixed
	 */
	public function getComposer(string $key)
	{
		if ($key === 'extra.codex' || Str::startsWith('extra.codex', $key)) {
			throw new InvalidArgumentException('Accessing "extra.codex" directly is not allowed');
		}

		return $this->composer->get($key);
	}

	/**
	 * @param array<string,mixed> $options The options to resolve.
	 *
	 * @return Dot<string,mixed>
	 */
	private function getResolvedConfigs(array $options): Dot
	{
		/** @var Dot<string,mixed> $configs */
		$configs = new Dot($options);
		$outputPath = $configs->get('scoper.output-path');

		/**
		 * If the "install-path" is set, resolve the value with the project path,
		 * which will ensure that the value returned will be the absolute path
		 * when accessed through the "scoper.output-path" key. For example,
		 * if the "install-path" is set to "dist-autoload", the resolved
		 * value will be:
		 *
		 * /path/to/project/dist-autoload
		 *
		 * ...instead of just:
		 *
		 * dist-autoload
		 */
		if (is_string($outputPath) && ! Val::isBlank($outputPath) && ! Path::isAbsolute($outputPath)) {
			$configs->set('scoper.output-path', Path::canonicalize($this->getProjectPath('/' . trim($outputPath, '/'))));
		}

		return $configs;
	}

	private function resolveConfigs(): void
	{
		$options = new OptionsResolver();
		$options->setDefault('scoper', function (OptionsResolver $resolver): void {
			$resolver->setDefined(['install-dev', 'exclude-namespaces', 'prefix', 'output-path']);
			$resolver->setAllowedTypes('prefix', 'string');
			$resolver->setAllowedTypes('output-path', 'string');
			$resolver->setAllowedTypes('exclude-namespaces', 'string[]');
			$resolver->setAllowedTypes('install-dev', 'string[]');
			$resolver->setDefaults([
				'output-path' => $this->getProjectPath(self::DEFAULT_SCOPER_OUTPUT_PATH),
			]);
			$resolver->setNormalizer('prefix', static function (Options $options, string $value): string {
				return trim(trim($value, '\\'));
			});
		});
		$options->setAllowedTypes('scoper', 'array');

		$configs = $this->composer->get('extra.codex');
		$configs = $this->getResolvedConfigs($options->resolve(is_array($configs) ? $configs : []));

		$this->configs = $configs;
	}
}
