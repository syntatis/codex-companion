<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles;

use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Syntatis\Codex\Companion\Contracts\Dumpable;
use Syntatis\Codex\Companion\Contracts\EditableFile;
use Syntatis\Utils\Val;

use function file_get_contents;
use function is_string;
use function preg_quote;
use function preg_replace;

class PHPCSFile implements Dumpable, EditableFile
{
	private SplFileInfo $file;

	private string $content;

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

		(new Filesystem())->dumpFile($this->file->getPathname(), $this->content);
	}

	private function doSearchReplace(): void
	{
		$content = file_get_contents($this->file->getPathname());

		if (Val::isBlank($content)) {
			return;
		}

		$content = preg_replace(
			[
				/**
				 * <file>plugin-name.php</file>
				 * <file>plugin-name.foo</file>
				 */
				'/(?<=[\>])' . preg_quote($this->searches['wp_plugin_slug']) . '(?=(\.\w{3}<))/', // File.
				/**
				 * Slug, Text Domain, etc.
				 *
				 * <foo bar="plugin-name"/>
				 */
				'/(?<=\")' . preg_quote($this->searches['wp_plugin_slug']) . '(?=\")/',
				'/(?<=\")' . preg_quote($this->searches['php_namespace']) . '(?=\")/',
			],
			[
				$this->replacements['wp_plugin_slug'],
				$this->replacements['wp_plugin_slug'],
				$this->replacements['php_namespace'],
			],
			$content,
		);

		if (! is_string($content) || Val::isBlank($content)) {
			return;
		}

		$this->content = $content;
	}
}
