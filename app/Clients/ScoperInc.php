<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Clients;

use Adbar\Dot;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Utils\Val;

use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function basename;
use function is_array;
use function is_string;
use function iterator_to_array;
use function json_decode;
use function json_encode;
use function trim;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/**
 * Abstraction for PHP-Scoper configuration.
 *
 * @see https://github.com/humbug/php-scoper/blob/main/docs/configuration.md
 */
class ScoperInc
{
	private Codex $codex;

	/** @var Dot<string,mixed> */
	private Dot $data;

	/** @param array<string,mixed> $configs */
	public function __construct(string $projectPath, array $configs = [])
	{
		$this->codex = new Codex($projectPath);
		$this->data = new Dot(array_merge(
			[
				'expose-global-constants' => true,
				'expose-global-classes' => true,
				'expose-global-functions' => true,
				'exclude-namespaces' => [],
				'exclude-files' => [],
			],
			$configs,
		));
	}

	public function addPatcher(callable $patcher): self
	{
		clone $self = $this;

		$self->data->push('patchers', $patcher);

		return $self;
	}

	/** @param iterable<SplFileInfo> $finder */
	public function addFinder(iterable $finder): self
	{
		clone $self = $this;

		$self->data->push('finders', $finder);

		return $self;
	}

	/**
	 * The list of files that will be left untouched during the scoping process.
	 *
	 * @see https://github.com/humbug/php-scoper/blob/main/docs/configuration.md#excluded-files
	 *
	 * @param iterable<mixed> $files A list of path of files to exclude. Each path may be an
	 *                                                   absolute or relative to the PHP-Scoper config file.
	 */
	public function excludeFiles(iterable $files): self
	{
		clone $self = $this;

		$merger = [];
		$current = $self->data->get('exclude-files', []);
		$current = is_array($current) ? $current : [];

		foreach ($files as $file) {
			if (is_string($file)) {
				$merger[] = $file;
				continue;
			}

			if ($file instanceof SplFileInfo) {
				$merger[] = (string) $file;
				continue;
			}
		}

		$self->data->set(
			'exclude-files',
			array_values(array_unique(array_merge($current, $merger))),
		);

		return $self;
	}

	/** @return array<string,mixed> */
	public function getAll(): array
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
		return [
			Finder::create()
				->files()
				->in(['vendor'])
				->notName('/composer.json|composer.lock|Makefile|LICENSE|CHANGELOG.*|.*\\.md|.*\\.dist|.*\\.rst/')
				->notPath(['bamarni', 'bin'])
				->exclude([
					'doc',
					'test',
					'test_old',
					'tests',
					'Tests',
					'Test',
					'vendor-bin',
				]),
			Finder::create()->append(['composer.json']),
		];
	}

	/** @return array<string> */
	private function getDefaultExcludeFiles(): array
	{
		return array_values(
			array_map(
				static fn ($file): string => $file->getRealPath(),
				iterator_to_array(
					Finder::create()
						->files()
						->in(['vendor'])
						->name([
							'*.html',
							'*.js',
							'*.css',
							'*.html.php',
						]),
				),
			),
		);
	}

	/** @return array<string> */
	private function getDefaultExcludeNamespaces(): array
	{
		$excludeNamespaces = [];
		$rootNamespace = $this->codex->getRootNamespace();

		if ($rootNamespace !== null) {
			$excludeNamespaces[] = trim($rootNamespace, '\\');
		}

		$namespaces = $this->codex->getConfig('scoper.exclude-namespaces');

		if (is_array($namespaces) && ! Val::isBlank($namespaces)) {
			$excludeNamespaces = array_merge(
				$excludeNamespaces,
				array_map(static function ($value) {
					return trim($value, '\\');
				}, $namespaces),
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
}
