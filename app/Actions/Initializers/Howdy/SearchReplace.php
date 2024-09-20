<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin\Actions\Initializers\Howdy;

use InvalidArgumentException;
use JsonException;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Syntatis\ComposerProjectPlugin\Exceptions\SearchReplaceException;
use Syntatis\Utils\Str;
use Syntatis\Utils\Val;

use function addslashes;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;
use function dirname;
use function fclose;
use function fgets;
use function file_get_contents;
use function fopen;
use function fwrite;
use function is_array;
use function is_resource;
use function is_string;
use function json_decode;
use function json_encode;
use function preg_quote;
use function preg_replace;
use function rename;
use function sprintf;
use function str_replace;
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
	 *
	 * @todo Retrieve these values from the project config.
	 */
	private const SEARCHES = [
		'vendor_prefix' => 'PluginName\\Vendor',
		'php_namespace' => 'PluginName',
		'wp_plugin_name' => 'Plugin Name',
		'wp_plugin_slug' => 'plugin-name',
	];

	public function __construct(UserInputs $userInputs)
	{
		$pluginSlug = $userInputs->get('wp_plugin_slug');

		if (Val::isBlank($pluginSlug)) {
			throw new InvalidArgumentException(
				sprintf('Invalid plugin slug argument. %s given.', var_export($pluginSlug)),
			);
		}

		$pluginSlug = Str::toSnakeCase(Str::toLowerCase($pluginSlug));

		$this->userInputs = $userInputs;
		$this->filesystem = new Filesystem();
		$this->replacements = array_merge(
			$this->userInputs->get(),
			[
				'camelcases' => Str::toCamelCase($pluginSlug),
				'snakecases' => Str::toSnakeCase($pluginSlug),
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

			case 'scoper.inc.php':
				$this->handleScoperInc($filePath);
				break;

			case 'plugin-name.pot':
				$newFilePath = dirname($filePath) . '/' . $this->replacements['wp_plugin_slug'] . '.pot';
				$this->filesystem->rename($filePath, $newFilePath);
				$this->handleFile($newFilePath);
				break;

			case 'plugin-name.php':
				$newFilePath = dirname($filePath) . '/' . $this->replacements['wp_plugin_slug'] . '.php';
				$this->filesystem->rename($filePath, $newFilePath);
				$this->handlePluginNameFile($newFilePath);
				break;

			default:
				$this->handleFile($filePath);
				break;
		}
	}

	/**
	 * Handle the plugin-name.php file.
	 *
	 * @throws SearchReplaceException When failed retrieving content of the file.
	 * @throws JsonException When failed encoding to JSON.
	 */
	private function handlePluginNameFile(string $filePath): void
	{
		$fileContent = file_get_contents($filePath);

		if ($fileContent === false || Val::isBlank($fileContent)) {
			throw new SearchReplaceException($filePath);
		}

		$modifiedContent = preg_replace(
			array_values($this->createSearchRegEx()),
			array_values($this->replacements),
			$fileContent,
		);

		if (! is_string($modifiedContent)) {
			return;
		}

		$this->filesystem->dumpFile($filePath, $modifiedContent);
	}

	/**
	 * Handle the scoper.inc.php file.
	 *
	 * @throws SearchReplaceException When failed retrieving content of the file.
	 * @throws JsonException When failed encoding to JSON.
	 */
	private function handleScoperInc(string $filePath): void
	{
		$fileContent = file_get_contents($filePath);

		if ($fileContent === false || Val::isBlank($fileContent)) {
			throw new SearchReplaceException($filePath);
		}

		$modifiedContent = str_replace(
			[
				'PluginName\\\\Vendor',
				'PluginName',
			],
			[
				addslashes($this->replacements['vendor_prefix']),
				addslashes($this->replacements['php_namespace']),
			],
			$fileContent,
		);

		$this->filesystem->dumpFile($filePath, $modifiedContent);
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

		if ($fileContent === false || Val::isBlank($fileContent)) {
			throw new SearchReplaceException($filePath);
		}

		$fileJson = json_decode($fileContent, true);

		if (
			is_array($fileJson) &&
			isset($fileJson['autoload']['psr-4']) &&
			is_array($fileJson['autoload']['psr-4'])
		) {
			$psr4 = $fileJson['autoload']['psr-4'];
			unset($psr4[self::SEARCHES['php_namespace'] . '\\']);

			$fileJson['autoload']['psr-4'] = array_merge(
				$psr4,
				[$this->replacements['php_namespace'] . '\\' => 'app/'],
			);
		}

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

		if ($fileContent === false || Val::isBlank($fileContent)) {
			throw new SearchReplaceException($filePath);
		}

		/** @var array<string, mixed> $fileJson */
		$fileJson = (array) json_decode($fileContent, true);

		if (isset($fileJson['files']) && is_array($fileJson['files'])) {
			$fileJson['files'] = array_map(
				fn ($file) => str_replace('plugin-name', $this->replacements['wp_plugin_slug'], is_string($file) ? $file : ''),
				$fileJson['files'],
			);
		}

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

		$tempFile = $this->filesystem->tempnam(dirname(__DIR__, 3) . '/tmp', 'howdy--');
		$tempHandle = fopen($tempFile, 'w');

		if (! is_resource($tempHandle)) {
			fclose($fileHandle);

			return;
		}

		$searchValues = array_values($this->createSearchRegEx());
		$replaceValues = array_values($this->replacements);

		while (($line = fgets($fileHandle)) !== false) {
			$modifiedLine = preg_replace(
				$searchValues,
				$replaceValues,
				$line,
			);

			if (! is_string($modifiedLine)) {
				continue;
			}

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

	/**
	 * Create the regular expressions for the search and replace.
	 *
	 * @return array<string>
	 */
	private function createSearchRegEx(): array
	{
		$searches = [
			'vendor_prefix' => self::SEARCHES['vendor_prefix'],
			'php_namespace' => self::SEARCHES['php_namespace'],
			'wp_plugin_name' => self::SEARCHES['wp_plugin_name'],
			'wp_plugin_slug' => self::SEARCHES['wp_plugin_slug'],
			'camelcases' => Str::toCamelCase(self::SEARCHES['wp_plugin_slug']),
			'snakecases' => Str::toSnakeCase(self::SEARCHES['wp_plugin_slug']),
		];

		return array_map(static function ($key, $value) {
			if ($key === 'wp_plugin_name') {
				return '/(?<!#\. )' . preg_quote($value) . '(?!:)/';
			}

			return '/' . preg_quote($value) . '/';
		}, array_keys($searches), $searches);
	}
}
