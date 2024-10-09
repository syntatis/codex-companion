<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles;

use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Syntatis\Codex\Companion\Contracts\Dumpable;
use Syntatis\Codex\Companion\Contracts\EditableFile;
use Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles;

use function array_keys;
use function array_map;
use function array_values;
use function dirname;
use function fclose;
use function fgets;
use function fopen;
use function fwrite;
use function is_resource;
use function is_string;
use function preg_quote;
use function preg_replace;
use function rename;

use const DIRECTORY_SEPARATOR;

/** @phpstan-import-type SearchReplaceItems from InitializeFiles */
class CommonFiles implements Dumpable, EditableFile
{
	private SplFileInfo $file;

	private string $filePath;

	private Filesystem $filesystem;

	/**
	 * @var array<string,string>
	 * @phpstan-var SearchReplaceItems
	 */
	private array $searches;

	/**
	 * @var array<string,string>
	 * @phpstan-var SearchReplaceItems
	 */
	private array $replacements;

	/**
	 * @param array<string,string> $searches
	 * @param array<string,string> $replacements
	 * @phpstan-param SearchReplaceItems $searches
	 * @phpstan-param SearchReplaceItems $replacements
	 */
	public function __construct(array $searches, array $replacements)
	{
		$this->searches = $searches;
		$this->replacements = $replacements;
	}

	public function setFile(SplFileInfo $file): void
	{
		$this->file = $file;
		$this->filePath = (string) $file;
	}

	public function dump(): void
	{
		$this->filesystem = new Filesystem();
		$this->doSearchReplace();

		if ($this->file->getBasename() !== $this->searches['wp_plugin_slug'] . '.php') {
			return;
		}

		$this->filesystem->rename(
			$this->filePath,
			Path::normalize(dirname($this->filePath) . DIRECTORY_SEPARATOR . $this->replacements['wp_plugin_slug'] . '.php'),
		);
	}

	private function doSearchReplace(): void
	{
		$fileHandle = fopen($this->filePath, 'r');

		if (! is_resource($fileHandle)) {
			return;
		}

		$tempFile = $this->filesystem->tempnam(dirname(__DIR__, 3) . '/tmp', '~codex-');
		$tempHandle = fopen($tempFile, 'w');

		if (! is_resource($tempHandle)) {
			fclose($fileHandle);

			return;
		}

		$searchValues = array_values($this->getRegEx());
		$replaceWith = array_values($this->replacements);

		while (($line = fgets($fileHandle)) !== false) {
			$modifiedLine = preg_replace(
				$searchValues,
				$replaceWith,
				$line,
			);

			if (! is_string($modifiedLine)) {
				continue;
			}

			fwrite($tempHandle, $modifiedLine);
		}

		fclose($fileHandle);
		fclose($tempHandle);

		if (! rename($tempFile, $this->filePath)) {
			$this->filesystem->remove($tempFile);

			return;
		}

		return;
	}

	/**
	 * Create the regular expressions for the search and replace.
	 *
	 * @return array<string>
	 */
	private function getRegEx(): array
	{
		return array_map(static function ($key, $value) {
			switch ($key) {
				case 'php_namespace':
					return '/(?<![\w+\d]\\\)' . preg_quote($value) . '(?=[\\\;])/m';

				case 'wp_plugin_name':
					return '/(?<=Plugin Name:)\s+\K' . preg_quote($value) . '/';

				case 'wp_plugin_description':
					return '/(?<=Description:)\s+\K' . preg_quote($value) . '/';

				case 'wp_plugin_slug':
				case 'kebabcase':
					return '/(?<=[\s|\"|\'|\.])' . preg_quote($value) . '(?=[\s|\"|\'|\\|\/|\-])/';

				case 'camelcase':
					return '/(?<=[\s|__|\$|\"|\'|\.])' . preg_quote($value) . '(?=[\s|\"|\'|\\|\/|\w])/';

				case 'snakecase':
					return '/(?<=[\s|__|\$|\"|\'|\.])' . preg_quote($value) . '(?=[\s|\"|\'|\\|\/|\_])/';

				default:
					return '/' . preg_quote($value) . '/';
			}
		}, array_keys($this->searches), $this->searches);
	}
}
