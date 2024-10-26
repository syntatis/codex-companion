<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers;

use RuntimeException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Contracts\Versionable;
use Syntatis\Codex\Companion\Contracts\VersionPatchIncrementable;
use Syntatis\Codex\Companion\Helpers\Versions\WPPluginRequiresAtLeast;
use Syntatis\Codex\Companion\Helpers\Versions\WPPluginRequiresPHP;
use Syntatis\Codex\Companion\Helpers\Versions\WPPluginTestedUpto;
use Syntatis\Codex\Companion\Helpers\Versions\WPPluginVersion;
use Syntatis\Codex\Companion\Helpers\Versions\WPTestedUpto;
use Syntatis\Codex\Companion\Helpers\Versions\WPVersion;
use Syntatis\Utils\Str;
use Syntatis\Utils\Val;
use Version\Exception\InvalidVersionString;
use Version\Extension\Build;
use Version\Extension\PreRelease;
use Version\Version;

use function array_merge;
use function file_get_contents;
use function in_array;
use function is_string;
use function ltrim;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function str_replace;
use function trim;

/**
 * Find and handle common properties of a WordPress plugin.
 *
 * Based on the WordPress plugin guideline, a plugin should have a plugin
 * file with a header comment containing the "Plugin Name:" header, at
 * the minimum. As such as the plugin file is required to determine
 * that a project is a WordPress.
 *
 * This class also follows WordPress guideline and recommended practices,
 * to parse the plugin properties, as follows:
 *
 * 1. The "Plugin Name" header should present in the main plugin file.
 *    This class will consider a PHP file that contains this header
 *    as main plugin file.
 * 2. The main plugin file should adopt the name of the plugin, e.g.,
 *    a plugin with the directory name `plugin-name` would have its
 *    main file named `plugin-name.php`. As such, the class will
 *    derive the plugin slug from the main plugin file name.
 * 3. The "Stable tag" header in the `readme.txt` file. Since the
 *    "Stable tag" header is one that WordPress.org will use to
 *    determine the plugin canonical version, this class will
 *    also use it to return the plugin version.
 *
 * @see https://developer.wordpress.org/plugins/plugin-basics/header-requirements/
 * @see https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/
 * @see https://semver.org/
 *
 * @phpstan-type Props = array{
 *      wp_plugin_name:non-empty-string,
 *      wp_plugin_slug:non-empty-string,
 *      wp_plugin_version:Versionable,
 *      wp_plugin_description?:non-empty-string|null,
 *      wp_plugin_requires_at_least?:Versionable|null,
 *      wp_plugin_requires_php?:Versionable|null,
 *      wp_plugin_tested_up_to?:Versionable|null,
 * }
 * @phpstan-type PluginHeaders = array{
 * 		wp_plugin_name:non-empty-string,
 * 		wp_plugin_description?:non-empty-string,
 * 		wp_plugin_requires_at_least?:Versionable,
 * 		wp_plugin_requires_php?:Versionable,
 * }
 * @phpstan-type ReadmeHeaders = array{
 * 		wp_plugin_version:Versionable&VersionPatchIncrementable,
 * 		wp_plugin_tested_up_to?:Versionable,
 * }
 */
class WPPluginProps
{
	private const VALID_PLUGIN_HEADERS = [
		'wp_plugin_name' => 'Plugin Name',
		'wp_plugin_description' => 'Description',
		'wp_plugin_requires_at_least' => 'Requires at least',
		'wp_plugin_requires_php' => 'Requires PHP',
	];

	private const VALID_README_HEADERS = [
		'wp_plugin_version' => 'Stable tag',
		'wp_plugin_tested_up_to' => 'Tested up to',
	];

	private const VERSIONING_HEADERS = [
		'wp_plugin_version',
		'wp_plugin_tested_up_to',
		'wp_plugin_requires_at_least',
		'wp_plugin_requires_php',
	];

	private Codex $codex;

	private SplFileInfo $pluginFile;

	private SplFileInfo $readmeFile;

	/** @phpstan-var PluginHeaders */
	private array $pluginHeaders;

	/** @phpstan-var ReadmeHeaders */
	private array $readmeHeaders;

	/** @phpstan-var Props */
	private array $props;

	public function __construct(Codex $codex)
	{
		$this->codex = $codex;
		$this->pluginFile = $this->findPluginFile();
		$this->readmeFile = $this->findPluginReadme();
		$this->pluginHeaders = $this->parsePluginHeaders();
		$this->readmeHeaders = $this->parseReadmeHeaders();
		$this->props = array_merge(
			[
				'wp_plugin_slug' => $this->findPluginSlug(),
			],
			$this->pluginHeaders,
			$this->readmeHeaders,
		);
	}

	/** @phpstan-return Props */
	public function getAll(): array
	{
		return $this->props;
	}

	/** @return string The plugin slug e.g. wordpress-seo, jetpack, etc. */
	public function getSlug(): string
	{
		return $this->props['wp_plugin_slug'];
	}

	/** @return string The plugin name e.g. Yoast SEO, Jetpack, etc. */
	public function getName(): string
	{
		return $this->props['wp_plugin_name'];
	}

	/** @return string The plugin short description in less than or equals to 150 characters. */
	public function getDescription(): ?string
	{
		return $this->props['wp_plugin_description'] ?? null;
	}

	/** @phptan-param key-of<VERSIONING_HEADERS> $key */
	public function getVersion(string $key): ?Versionable
	{
		if (! in_array($key, self::VERSIONING_HEADERS, true)) {
			return null;
		}

		$version = $this->props[$key];

		return $version instanceof Versionable ? $version : null;
	}

	/**
	 * Retrieve the path to the main plugin file.
	 *
	 * The plugin main file is the file that WordPress will load to initialize
	 * the plugin. The file contains the headers that WordPress will use to
	 * identify the plugin, such as the plugin name, the plugin version,
	 * the minimum WordPress version required, etc.
	 *
	 * @see https://developer.wordpress.org/plugins/plugin-basics/
	 */
	public function getFile(): SplFileInfo
	{
		return $this->pluginFile;
	}

	/**
	 * Parse the plugin headers from the main plugin file.
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_file_data/
	 *
	 * @phpstan-return PluginHeaders
	 */
	private function parsePluginHeaders(): array
	{
		$fileData = file_get_contents($this->pluginFile->getRealPath(), false, null, 0, 8 * 1024);

		if ($fileData === false) {
			$fileData = '';
		}

		// Make sure we catch CR-only line endings.
		$fileData = str_replace("\r", "\n", $fileData);

		/** @phpstan-var PluginHeaders $headers */
		$headers = [];

		foreach (self::VALID_PLUGIN_HEADERS as $field => $regex) {
			preg_match('/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . preg_quote($regex) . ':\s*(.*)$/mi', $fileData, $matches);

			$value = trim((string) preg_replace('/\s*(?:\*\/|\?>).*/', '', $matches[1] ?? ''));

			if ($field === 'wp_plugin_name') {
				if (Val::isBlank($value)) {
					throw new RuntimeException('Unable to find the "Plugin Name" header in the plugin file.');
				}

				$headers[$field] = $value;

				continue;
			}

			if (Val::isBlank($value)) {
				continue;
			}

			switch ($field) {
				case 'wp_plugin_requires_at_least':
					$version = self::normalizeVersion($value);
					$headers[$field] = new WPPluginRequiresAtLeast($version->toString());
					break;
				case 'wp_plugin_requires_php':
					$version = self::normalizeVersion($value);
					$headers[$field] = new WPPluginRequiresPHP($version->toString());
					break;
				default:
					$headers[$field] = $value;
					break;
			}
		}

		return $headers;
	}

	/**
	 * Parse the readme headers from the readme.txt file.
	 *
	 * @phpstan-return ReadmeHeaders
	 *
	 * @throws InvalidVersionString
	 */
	private function parseReadmeHeaders(): array
	{
		$fileData = file_get_contents($this->readmeFile->getRealPath(), false, null, 0, 8 * 1024);

		if ($fileData === false) {
			$fileData = '';
		}

		/** @phpstan-var ReadmeHeaders $headers */
		$headers = [];

		foreach (self::VALID_README_HEADERS as $field => $regex) {
			preg_match('/^(?:\s*)\K' . preg_quote($regex) . ':\s*(v?[\d\.]+)$/mi', $fileData, $matches);

			$value = $matches[1] ?? '';

			/**
			 * The `wp_plugin_version` field is required.
			 * If the field is not found, or invalid, throw an error.
			 */
			if ($field === 'wp_plugin_version') {
				$headers[$field] = new WPPluginVersion($value);
				continue;
			}

			if (Val::isBlank($value)) {
				continue;
			}

			switch ($field) {
				case 'wp_plugin_tested_up_to':
					$headers[$field] = new WPPluginTestedUpto($value);
					break;
			}
		}

		return $headers;
	}

	/**
	 * Retrieve the project slug.
	 *
	 * The project slug is determined from the plugin file, which should has
	 * the expected header structure as explained in the WordPress Plugin
	 * Handbook.
	 *
	 * @see https://developer.wordpress.org/plugins/plugin-basics/header-requirements/
	 *
	 * @return string The plugin name e.g. plugin-name, yoast-seo, etc.
	 * @phpstan-return non-empty-string
	 */
	private function findPluginSlug(): string
	{
		$file = $this->getFile();

		/**
		 * The getFile method would throw an error if the file could not be found,
		 * it is safe to assume that the base would be a `non-empty-string`.
		 *
		 * @phpstan-var non-empty-string $slug
		 */
		$slug = $file->getBasename('.php');

		return Str::toKebabCase(Str::toLowerCase($slug));
	}

	/** @throws RuntimeException If the file could not be found. */
	private function findPluginFile(): SplFileInfo
	{
		$results = Finder::create()
			->ignoreVCS(true)
			->ignoreDotFiles(true)
			->files()
			->depth(0)
			->name('*.php')
			->contains('/^(?:[ \t]*<\?php)?[ \t\/*#@]*Plugin Name:\s*(.*)$/mi')
			->sortByName()
			->in($this->codex->getProjectPath());

		foreach ($results as $item) {
			return $item;
		}

		throw new RuntimeException('Unable to find the WordPress plugin main file.');
	}

	/** @throws RuntimeException If the file could not be found. */
	private function findPluginReadme(): SplFileInfo
	{
		$results = Finder::create()
			->files()
			->depth(0)
			->name('readme.txt')
			->in($this->codex->getProjectPath());

		foreach ($results as $item) {
			return $item;
		}

		throw new RuntimeException('Unable to find the WordPress plugin readme.txt file.');
	}

	/**
	 * Normalize version before it's validated.
	 */
	private static function normalizeVersion(string $version): Version
	{
		/**
		 * Modify the Semver RegEx rules to match version without the patch number.
		 *
		 * @see https://semver.org/#is-there-a-suggested-regular-expression-regex-to-check-a-semver-string
		 */
		$matched = preg_match('/^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)(?:\.(?P<patch>0|[1-9]\d*))?(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/', ltrim($version, 'v'), $matches);

		$major = $matches['major'] ?? null;
		$minor = $matches['minor'] ?? null;

		if (Val::isBlank($major) || Val::isBlank($minor)) {
			throw InvalidVersionString::notParsable($version);
		}

		$patch = $matches['patch'] ?? 0;
		$prerelease = $matches['prerelease'] ?? null;
		$buildmetadata = $matches['buildmetadata'] ?? null;

		$version = Version::from(
			(int) $major,
			(int) $minor,
			(int) $patch,
			is_string($prerelease) ? PreRelease::fromString($prerelease) : null,
			is_string($buildmetadata) ? Build::fromString($buildmetadata) : null,
		);

		return $version;
	}
}
