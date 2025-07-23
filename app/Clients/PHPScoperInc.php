<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Clients;

use Adbar\Dot;
use Isolated\Symfony\Component\Finder\Finder as IsolatedFinder;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Utils\Val;

use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function basename;
use function class_alias;
use function class_exists;
use function is_array;
use function iterator_to_array;
use function json_decode;
use function json_encode;
use function trim;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

// Exposes the finder used by PHP-Scoper PHAR to allow its usage in the configuration file.
if (class_exists(IsolatedFinder::class) === false) {
	class_alias(Finder::class, IsolatedFinder::class);
}

/**
 * Abstraction for PHP-Scoper configuration.
 *
 * @see https://github.com/humbug/php-scoper/blob/main/docs/configuration.md
 *
 * @phpstan-type FinderConfigs = array{not-path?:array<string>,exclude?:array<string>}
 */
class PHPScoperInc
{
	private Codex $codex;

	/** @var Dot<string,mixed> */
	private Dot $data;

	/** @phpstan-var FinderConfigs|array{} */
	private array $finderConfigs = [];

	public function __construct(string $projectPath)
	{
		$this->codex = new Codex($projectPath);
		$this->data = new Dot(array_merge(
			[
				'expose-global-constants' => $this->codex->getConfig('scoper.expose-global-constants'),
				'expose-global-classes' => $this->codex->getConfig('scoper.expose-global-classes'),
				'expose-global-functions' => $this->codex->getConfig('scoper.expose-global-functions'),
				'exclude-namespaces' => $this->codex->getConfig('scoper.exclude-namespaces'),
			],
		));
		$this->excludeFiles();
		$this->finderConfigs = (array) ($this->codex->getConfig('scoper.finder') ?? []);
	}

	public function withPatcher(callable $patcher): self
	{
		$self = clone $this;

		$self->data->push('patchers', $patcher);

		return $self;
	}

	/**
	 * @param iterable<SplFileInfo>|array<string,mixed> $finder
	 * @phpstan-param iterable<SplFileInfo>|FinderConfigs $finder
	 */
	public function withFinder(iterable $finder): self
	{
		$self = clone $this;

		if (is_array($finder)) {
			$self->finderConfigs = [
				'not-path' => array_unique(
					array_merge(
						$self->finderConfigs['not-path'] ?? [],
						(array) ($finder['not-path'] ?? []),
					),
				),
				'exclude' => array_unique(
					array_merge(
						$self->finderConfigs['exclude'] ?? [],
						(array) ($finder['exclude'] ?? []),
					),
				),
			];
		} else {
			$self->data->push('finders', $finder);
		}

		return $self;
	}

	/** @return array<string,mixed> */
	public function get(): array
	{
		/**
		 * These configurations contain defaults to make it works out of the box.
		 * Users may add to these configurations through the methods provided.
		 */
		$this->data->merge('finders', $this->getDefaultFinders());
		$this->data->merge('patchers', $this->getDefaultPatchers());
		$this->data->merge('exclude-files', $this->getDefaultExcludeFiles());
		$this->data->merge('exclude-namespaces', $this->getDefaultExcludeNamespaces());

		/**
		 * The following configs are set in the `composer.json`. Any changes have
		 * to be done in the `composer.json` file.
		 */
		$this->data->set('prefix', $this->codex->getConfig('scoper.prefix'));

		return $this->data->all();
	}

	/** @return array<iterable<SplFileInfo>> */
	private function getDefaultFinders(): array
	{
		$notPath = $this->finderConfigs['not-path'] ?? [];
		$exclude = $this->finderConfigs['exclude'] ?? [];

		return [
			IsolatedFinder::create()
				->files()
				->in(['vendor'])
				->notName('/composer.json|phpunit.xml|phpcs.xml|pint.json|composer.lock|Makefile|LICENSE|CHANGELOG.*|.*\\.md|.*\\.dist|.*\\.rst/')
				->notPath($notPath)
				->exclude(
					array_merge(
						[
							'.github',
							'Test',
							'Tests',
							'bin',
							'doc',
							'test',
							'test_old',
							'tests',
							'vendor-bin',
						],
						$exclude,
					),
				),
			IsolatedFinder::create()->append(['composer.json']),
		];
	}

	/** @return array<string> */
	private function getDefaultExcludeFiles(): array
	{
		return array_values(
			array_map(
				static fn ($file): string => $file->getRealPath(),
				iterator_to_array(
					IsolatedFinder::create()
						->files()
						->in(['vendor'])
						->name(['*.html.php']),
				),
			),
		);
	}

	/** @return array<string> */
	private function getDefaultExcludeNamespaces(): array
	{
		/**
		 * @var array<string> $excludeNamespaces
		 * @phpstan-var list<string> $excludeNamespaces
		 */
		$excludeNamespaces = [];

		/**
		 * @var array<string> $namespaces
		 * @see \Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles\ComposerFile::getConfigExcludeNamespaces
		 * @phpstan-var list<string> $namespaces
		 */
		$namespaces = $this->codex->getConfig('scoper.exclude-namespaces');
		$rootNamespace = $this->codex->getRootNamespace();

		if ($rootNamespace !== null) {
			$excludeNamespaces[] = trim($rootNamespace, '\\');
		}

		if (! Val::isBlank($namespaces)) {
			$namespaces = array_map(
				static fn ($value) => trim($value, '\\'),
				$namespaces,
			);
			$excludeNamespaces = array_merge(
				$excludeNamespaces,
				$namespaces,
			);
		}

		return $excludeNamespaces;
	}

	/** @return array<callable> */
	private function getDefaultPatchers(): array
	{
		return [
			static function (string $filePath, string $prefix, string $content): string {
				if (basename($filePath) === 'composer.json') {
					return json_encode(
						(array) json_decode($content, true),
						JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT,
					);
				}

				return $content;
			},
		];
	}

	/**
	 * The list of files that will be left untouched during the scoping process.
	 *
	 * @see https://github.com/humbug/php-scoper/blob/main/docs/configuration.md#excluded-files
	 */
	private function excludeFiles(): void
	{
		/** @var array<array{in:string|array<string>,name?:string|array<string>}> $configs List of directory and name file to exclude. */
		$configs = $this->codex->getConfig('scoper.exclude-files');
		$excludes = [];

		foreach ($configs as $conf) {
			$name = $conf['name'] ?? null;
			$in = $conf['in'];

			if (Val::isBlank($name) && Val::isBlank($in)) {
				continue;
			}

			$finder = IsolatedFinder::create()->files();
			$finder->in($in);

			if (! Val::isBlank($name)) {
				$finder->name($name);
			}

			$paths = array_map(
				static fn ($file): string => $file->getRealPath(),
				iterator_to_array($finder),
			);
			$excludes = array_merge($excludes, array_values($paths));
		}

		$this->data->set('exclude-files', $excludes);
	}
}
