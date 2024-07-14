<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin\Actions\Initializers\WPStarterPlugin;

use InvalidArgumentException;
use JsonException;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Syntatis\ComposerProjectPlugin\Exceptions\SearchReplaceException;

use function array_merge;
use function array_values;
use function dirname;
use function fclose;
use function fgets;
use function file_get_contents;
use function fopen;
use function fwrite;
use function is_resource;
use function json_decode;
use function json_encode;
use function rename;
use function sprintf;
use function str_replace;
use function Syntatis\Utils\camelcased;
use function Syntatis\Utils\is_blank;
use function Syntatis\Utils\lowercased;
use function Syntatis\Utils\macrocased;
use function Syntatis\Utils\snakecased;
use function var_export;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

class SearchReplace
{
	private UserInputs $userInputs;

	private Filesystem $filesystem;

	/** @var array<string> */
	private array $replacements = [];

	/**
	 * Please note that the order of the keys in the array should
	 * match the order of the values in the $replacements prop.
	 */
	private const SEARCHES = [
		'php_namespace' => 'WPStarterPlugin',
		'project_name' => 'syntatis/wp-starter-plugin',
		'wp_plugin_name' => 'WP Starter Plugin',
		'wp_plugin_slug' => 'wp-starter-plugin',
		'variables' => 'wpStarterPlugin',
		'constants' => 'WP_STARTER_PLUGIN',
	];

	public function __construct(UserInputs $userInputs)
	{
		$projectName = $userInputs->get('project_name');

		if (is_blank($projectName)) {
			throw new InvalidArgumentException(
				sprintf('Invalid "wp_plugin_slug" argument. %s given.', var_export($projectName)),
			);
		}

		$projectName = snakecased(lowercased(str_replace(['/', '-'], '_', $projectName)));

		$this->userInputs = $userInputs;
		$this->filesystem = new Filesystem();
		$this->replacements = array_merge(
			$this->userInputs->get(),
			[
				'variables' => camelcased($projectName),
				'constants' => macrocased($projectName),
			],
		);
	}

	public function file(SplFileInfo $fileInfo): void
	{
		if (! $fileInfo->isFile()) {
			return;
		}

		$filePath = (string) $fileInfo; // File absolute path.

		switch ($fileInfo->getBasename()) {
			case 'composer.json':
				$this->handleFileComposer($filePath);
				break;

			case 'package.json':
				$this->handleFilePackage($filePath);
				break;

			case 'wp-starter-plugin.php':
				$newFilePath = dirname($filePath) . '/' . $this->replacements['wp_plugin_slug'] . '.php';
				$this->filesystem->rename($filePath, $newFilePath);
				$this->handleFile($newFilePath);
				break;

			default:
				$this->handleFile($filePath);
				break;
		}
	}

	/**
	 * Handle the composer.json file.
	 *
	 * @throws SearchReplaceException When failed retrieving content of the file.
	 * @throws JsonException When failed encoding to JSON.
	 */
	private function handleFileComposer(string $filePath): void
	{
		$fileContent = file_get_contents($filePath);

		if ($fileContent === false || is_blank($fileContent)) {
			throw new SearchReplaceException($filePath);
		}

		$fileJson = (array) json_decode($fileContent, true);
		$fileJson['name'] = $this->replacements['project_name'];
		$fileJson['autoload']['psr-4'] = [$this->replacements['php_namespace'] . '\\' => 'app/'];
		$encodedJson = json_encode($fileJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

		$this->filesystem->dumpFile($filePath, $encodedJson);
	}

	/**
	 * Handle the package.json file.
	 *
	 * @throws SearchReplaceException When failed retrieving content of the file.
	 * @throws JsonException When failed encoding to JSON.
	 */
	private function handleFilePackage(string $filePath): void
	{
		$fileContent = file_get_contents($filePath);

		if ($fileContent === false || is_blank($fileContent)) {
			throw new SearchReplaceException($filePath);
		}

		$fileJson = (array) json_decode($fileContent, true);
		$fileJson['name'] = '@' . $this->replacements['project_name'];
		$encodedJson = json_encode($fileJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

		$this->filesystem->dumpFile($filePath, $encodedJson);
	}

	/**
	 * Handle other files.
	 */
	private function handleFile(string $filePath): void
	{
		$fileHandle = fopen($filePath, 'r');

		if (! is_resource($fileHandle)) {
			return;
		}

		$tempFile = $this->filesystem->tempnam(dirname(__DIR__, 3) . '/tmp', 'wp-starter-plugin-');
		$tempHandle = fopen($tempFile, 'w');

		if (! is_resource($tempHandle)) {
			fclose($fileHandle);

			return;
		}

		while (($line = fgets($fileHandle, 64)) !== false) {
			$modifiedLine = str_replace(
				array_values(self::SEARCHES),
				array_values($this->replacements),
				$line,
			);
			fwrite($tempHandle, $modifiedLine);
		}

		fclose($fileHandle);
		fclose($tempHandle);

		if (! rename($tempFile, $filePath)) {
			$this->filesystem->remove($tempFile);

			return;
		}

		return;
	}
}