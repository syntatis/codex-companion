<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles;

use Adbar\Dot;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Syntatis\Codex\Companion\Contracts\Dumpable;
use Syntatis\Codex\Companion\Contracts\EditableFile;
use Syntatis\Utils\Arr;
use Syntatis\Utils\Val;

use function array_filter;
use function array_map;
use function array_unique;
use function dot;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function preg_quote;
use function preg_replace;
use function trim;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

class ComposerFile implements Dumpable, EditableFile
{
	/** @phpstan-var Dot<string,mixed> */
	private ?Dot $data = null;

	private SplFileInfo $file;

	/** @var array<string,string> */
	private array $searches;

	/** @var array<string,string> */
	private array $replacements;

	/**
	 * @param array<string,string> $searches
	 * @param array<string,string> $replacements
	 */
	public function __construct(array $searches, array $replacements)
	{
		$this->searches = $searches;
		$this->replacements = $replacements;
	}

	public function setFile(SplFileInfo $file): void
	{
		$this->file = $file;
	}

	public function dump(): void
	{
		$this->doSearchReplace();

		if (! ($this->data instanceof Dot)) {
			return;
		}

		$filesystem = new Filesystem();
		$filesystem->dumpFile(
			$this->file->getPathname(),
			json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
		);
	}

	private function doSearchReplace(): void
	{
		$content = file_get_contents($this->file->getPathname());
		$data = json_decode((string) $content, true);

		if (! is_array($data)) {
			return;
		}

		$this->data = dot($data);
		$this->data->set('extra.codex.scoper.prefix', trim($this->replacements['php_vendor_prefix'], '\\'));

		$autoload = $this->getAutoloads('autoload.psr-4');

		if (is_array($autoload) && ! Val::isBlank($autoload)) {
			$this->data->set('autoload.psr-4', $autoload);
		}

		$autoloadDev = $this->getAutoloads('autoload-dev.psr-4');

		if (is_array($autoloadDev) && ! Val::isBlank($autoloadDev)) {
			$this->data->set('autoload-dev.psr-4', $autoloadDev);
		}

		$excludeNamespaces = $this->getConfigExcludeNamespaces();

		if (is_array($excludeNamespaces) && ! Val::isBlank($excludeNamespaces)) {
			$this->data->set(
				'extra.codex.scoper.exclude-namespaces',
				$excludeNamespaces,
			);
		}

		$this->handleScripts();
	}

	/** @phpstan-return array<string,string|list<string>>|null */
	private function getAutoloads(string $key): ?array
	{
		if (! ($this->data instanceof Dot)) {
			return null;
		}

		$autoloads = $this->data->get($key) ?? null;

		if (! is_array($autoloads) || Val::isBlank($autoloads)) {
			return null;
		}

		foreach ($autoloads as $namespace => $dirs) {
			unset($autoloads[$namespace]);

			$newNamespace = preg_replace(
				'/^' . preg_quote($this->searches['php_namespace'], '/') . '\\\\/',
				$this->replacements['php_namespace'] . '\\',
				$namespace,
			);

			$autoloads[$newNamespace] = $dirs;
		}

		return $autoloads;
	}

	/** @phpstan-return list<string>|null */
	private function getConfigExcludeNamespaces(): ?array
	{
		if (! ($this->data instanceof Dot)) {
			return null;
		}

		$namespaces = $this->data->get('extra.codex.scoper.exclude-namespaces') ?? [];

		if (Val::isBlank($namespaces) || ! is_array($namespaces)) {
			return null;
		}

		$namespaces = array_filter(
			array_unique($namespaces),
			static fn ($namespace) => is_string($namespace),
		);

		if (! Arr::isList($namespaces)) {
			return null;
		}

		return array_map(
			function (string $namespace) {
				if (trim($namespace, '\\') === trim($this->searches['php_namespace'], '\\')) {
					return trim($this->replacements['php_namespace'], '\\');
				}

				return $namespace;
			},
			$namespaces,
		);
	}

	private function handleScripts(): void
	{
		if (! ($this->data instanceof Dot)) {
			return;
		}

		$scripts = $this->data->get('scripts') ?? null;

		if (! is_array($scripts) || Val::isBlank($scripts)) {
			return;
		}

		$searchReplace = function ($script) {
			if (is_string($script)) {
				return $this->searchReplaceSlug($script);
			}

			if (is_array($script)) {
				$script = array_filter($script, static fn ($s) => is_string($s));

				return array_map(
					fn ($s) => $this->searchReplaceSlug($s),
					$script,
				);
			}

			return $script;
		};

		foreach ($scripts as $name => $script) {
			$this->data->set('scripts.' . $name, $searchReplace($script));
		}
	}

	public function searchReplaceSlug(string $subject): ?string
	{
		$search = preg_quote($this->searches['wp_plugin_slug']);

		return preg_replace(
			[
				'/(?<=\s|\=|\/)' . $search . '$/',
				'/(?<=\s|\=|\/)' . $search . '(?=[\s|\.])/',
			],
			$this->replacements['wp_plugin_slug'],
			$subject,
		);
	}
}
