<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles;

use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Syntatis\Codex\Companion\Contracts\Dumpable;
use Syntatis\Codex\Companion\Contracts\EditableFile;

use function array_map;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function preg_quote;
use function preg_replace;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

class PackageFile implements Dumpable, EditableFile
{
	private SplFileInfo $file;

	/** @var array<string,mixed> */
	private array $data;

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

		(new Filesystem())->dumpFile(
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

		if (! isset($data['files']) || ! is_array($data['files'])) {
			return;
		}

		$data['files'] = array_map(
			fn ($file) => preg_replace(
				'/^' . preg_quote($this->searches['wp_plugin_slug']) . '(?=(\.\w{3}))/',
				$this->replacements['wp_plugin_slug'],
				is_string($file) ? $file : '',
			),
			$data['files'],
		);

		$this->data = $data;
	}
}
