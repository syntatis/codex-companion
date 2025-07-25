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

use function array_key_exists;
use function in_array;
use function is_array;
use function is_string;
use function method_exists;
use function sprintf;
use function trim;

class Codex
{
	/**
	 * The directory to determine the root namespace.
	 */
	private const ROOT_NAMESPACE_DIR = 'app/';

	private const DEFAULT_SCOPER_OUTPUT_PATH = 'dist/autoload';

	private ComposerCollection $composer;

	/** @var Dot<string,mixed> */
	private Dot $configs;

	private string $projectPath = '';

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
		$projectPath = $this->projectPath;

		if (! Val::isBlank($path)) {
			if (Str::startsWith($path, '..') || Path::isAbsolute($path)) {
				throw new InvalidArgumentException(
					sprintf('The path "%s" is invalid. The path must be relative to the project path.', $path),
				);
			}

			$path = trim($path, '\\/.');
			$projectPath = Path::normalize($this->projectPath . '/' . $path);
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
		$outputPath = $configs->get('scoper.output-dir');

		/**
		 * If the "install-path" is set, resolve the value with the project path,
		 * which will ensure that the value returned will be the absolute path
		 * when accessed through the "scoper.output-dir" key. For example,
		 * if the "install-path" is set to "dist-autoload", the resolved
		 * value will be:
		 *
		 * /path/to/project/dist-autoload
		 *
		 * ...instead of just:
		 *
		 * dist-autoload
		 */
		if (is_string($outputPath) && ! Val::isBlank($outputPath)) {
			$configs = $configs->set('scoper.output-dir', Path::canonicalize($this->getProjectPath($outputPath)));
		}

		return $configs;
	}

	private function resolveConfigs(): void
	{
		$options = new OptionsResolver();
		$scoper = static function (OptionsResolver $resolver): void {
			$resolver->setDefined([
				'install-dev',
				'exclude-namespaces',
				'exclude-files',
				'expose-global-constants',
				'expose-global-classes',
				'expose-global-functions',
				'prefix',
				'output-dir',
				'finder',
			]);
			$resolver->setAllowedTypes('prefix', 'string');
			$resolver->setAllowedTypes('output-dir', 'string');
			$resolver->setAllowedTypes('expose-global-constants', 'bool');
			$resolver->setAllowedTypes('expose-global-classes', 'bool');
			$resolver->setAllowedTypes('expose-global-functions', 'bool');
			$resolver->setAllowedTypes('exclude-files', 'array');
			$resolver->setAllowedTypes('exclude-namespaces', 'string[]');
			$resolver->setAllowedTypes('install-dev', 'string[]');
			$resolver->setDefaults([
				'output-dir' => self::DEFAULT_SCOPER_OUTPUT_PATH,
				'expose-global-constants' => true,
				'expose-global-classes' => true,
				'expose-global-functions' => true,
				'exclude-namespaces' => [],
				'exclude-files' => [],
				'install-dev' => [],
				'finder' => [],
			]);

			$finder = static function (OptionsResolver $resolver): void {
				$resolver->setDefined(['not-path', 'exclude']);
				$resolver->setAllowedTypes('not-path', 'string[]');
				$resolver->setAllowedTypes('exclude', 'string[]');
			};

			/**
			 * Since symfony/options-resolver 7.3: Defining nested options via "Symfony\Component\OptionsResolver\OptionsResolver::setDefault()"
			 * is deprecated and will be removed in Symfony 8.0, use "setOptions()" method instead.
			 *
			 * @see https://github.com/syntatis/codex-companion/issues/70
			 */
			if (method_exists($resolver, 'setOptions')) {
				$resolver->setOptions('finder', $finder);
			} else {
				$resolver->setDefault('finder', $finder);
			}

			$resolver->setNormalizer('prefix', static function (Options $options, string $value): string {
				return trim(trim($value, '\\'));
			});

			$resolver->setNormalizer('exclude-files', static function (Options $options, $value): array {
				foreach ($value as $index => $item) {
					if (! is_array($item)) {
						throw new InvalidArgumentException(sprintf('Each parameter must be an array at index %d.', $index));
					}

					// "in" is required
					if (! array_key_exists('in', $item)) {
						throw new InvalidArgumentException(sprintf('Missing required "in" key at index %d.', $index));
					}

					// "in" must be string or array<string>
					if (! is_string($item['in']) && ! is_array($item['in'])) {
						throw new InvalidArgumentException(sprintf('"in" must be a string or array of strings at index %d.', $index));
					}

					if (is_array($item['in']) && ! self::isStringArray($item['in'])) {
						throw new InvalidArgumentException(sprintf('"in" must be an array of strings at index %d.', $index));
					}

					// "name" is optional, but must be string or array<string> if present
					if (! isset($item['name'])) {
						continue;
					}

					if (! is_string($item['name']) && ! is_array($item['name'])) {
						throw new InvalidArgumentException(sprintf('"name" must be a string or array of strings at index %d.', $index));
					}

					if (is_array($item['name']) && ! self::isStringArray($item['name'])) {
						throw new InvalidArgumentException(sprintf('"name" must be an array of strings at index %d.', $index));
					}
				}

				return $value;
			});
		};

		/**
		 * Since symfony/options-resolver 7.3: Defining nested options via "Symfony\Component\OptionsResolver\OptionsResolver::setDefault()"
		 * is deprecated and will be removed in Symfony 8.0, use "setOptions()" method instead.
		 *
		 * @see https://github.com/syntatis/codex-companion/issues/70
		 */
		if (method_exists($options, 'setOptions')) {
			$options->setOptions('scoper', $scoper);
		} else {
			$options->setDefault('scoper', $scoper);
		}

		$configs = $this->composer->get('extra.codex');
		$configs = $this->getResolvedConfigs($options->resolve(is_array($configs) ? $configs : []));

		$this->configs = $configs;
	}

	/**
	 * Check if all items in the array are strings.
	 *
	 * @param array<mixed> $array The array to check.
	 *
	 * @return bool True if all items are strings, false otherwise.
	 */
	private static function isStringArray(array $array): bool
	{
		foreach ($array as $item) {
			if (! is_string($item)) {
				return false;
			}
		}

		return true;
	}
}
