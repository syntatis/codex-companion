<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles;

use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Syntatis\Codex\Companion\Contracts\Dumpable;
use Syntatis\Codex\Companion\Contracts\EditableFile;
use Syntatis\Utils\Val;

use function dirname;
use function file_get_contents;
use function is_string;
use function preg_quote;
use function preg_replace;

use const DIRECTORY_SEPARATOR;

class POTFile implements Dumpable, EditableFile
{
	private string $filePath;

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
		$this->filePath = (string) $file;
	}

	public function dump(): void
	{
		$this->doSearchReplace();

		$filesystem = new Filesystem();
		$filesystem->dumpFile($this->filePath, $this->content);
		$filesystem->rename(
			$this->filePath,
			Path::normalize(dirname($this->filePath) . DIRECTORY_SEPARATOR . $this->replacements['wp_plugin_slug'] . '.pot'),
		);
	}

	private function doSearchReplace(): void
	{
		$content = file_get_contents($this->filePath);

		if (Val::isBlank($content)) {
			return;
		}

		$content = preg_replace(
			[
				/**
				 * "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/howdy\n"
				 */
				'/(?<=\/\/wordpress\.org\/support\/plugin\/)[a-zA-Z\-\_0-9]+/',

				/**
				 * "X-Domain: plugin-name\n"
				 * #: plugin-name.php
				 */
				'/(?<=\:)\s+?\K' . preg_quote($this->searches['wp_plugin_slug']) . '(?=[\\\\"\s\.])/',

				/**
				 * "Project-Id-Version: Plugin Name 1.0.0\n"
				 * msgid "Plugin Name"
				 *
				 * But does not match:
				 *
				 * #. Plugin Name of the plugin
				 */
				'/(?<!\#\.\s)(?<=[\s\"])' . preg_quote($this->searches['wp_plugin_name']) . '(?=[\"\s])/',
			],
			[
				$this->replacements['wp_plugin_slug'],
				$this->replacements['wp_plugin_slug'],
				$this->replacements['wp_plugin_name'],
			],
			$content,
		);

		if (! is_string($content) || Val::isBlank($content)) {
			return;
		}

		$this->content = $content;
	}
}
