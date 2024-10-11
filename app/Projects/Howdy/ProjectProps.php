<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Projects\Howdy;

use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Utils\Str;
use Syntatis\Utils\Val;

use function file_get_contents;
use function is_string;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function rtrim;
use function str_replace;
use function trim;

/**
 * Parse the project properties, such as the plugin name, the plugin slug,
 * the namespace, etc.
 *
 * These properties are gathered from several different files in the project.
 * The project is provided with an opinionated structure so it is assumed
 * that the files required to find these properties such as the plugin
 * file, the readme file, etc. are already in place in the expected
 * location with the expected content structure.
 *
 * For example, the project should has the plugin main file with the headers,
 * it should has the scoper.inc.php file with the expected prefix, it has
 * the composer.json file with a namespace pointing to the `app/`, etc.
 */
class ProjectProps
{
	private Codex $codex;

	private ?SplFileInfo $pluginFile;

	/** @var array<string,string> */
	private ?array $pluginHeaders = null;

	/** @var array<string,string|null> */
	private array $props = [];

	public function __construct(Codex $codex)
	{
		$this->codex = $codex;
		$this->pluginFile = $this->findPluginFile();
		$this->pluginHeaders = $this->parsePluginHeaders();
		$this->props = [
			'php_vendor_prefix' => $this->findVendorPrefix(),
			'php_namespace' => $this->findNamespace(),
			'wp_plugin_name' => $this->findPluginName(),
			'wp_plugin_slug' => $this->findPluginSlug(),
		];

		$description = $this->findPluginDescription();

		if (Val::isBlank($description)) {
			return;
		}

		$this->props['wp_plugin_description'] = $description;
	}

	/** @return array<string,string|null> */
	public function get(): array
	{
		return $this->props;
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
	 * @return ?string The plugin name e.g. plugin-name, yoast-seo, etc.
	 */
	public function getPluginSlug(): ?string
	{
		return $this->props['wp_plugin_slug'] ?? null;
	}

	private function findPluginSlug(): ?string
	{
		$file = $this->getPluginFile();

		if ($file instanceof SplFileInfo) {
			$slug = $file->getBasename('.php');

			return Str::toKebabCase(Str::toLowerCase($slug));
		}

		return null;
	}

	/** @return string The plugin name e.g. Plugin Name, Yoast SEO, etc. */
	public function getPluginName(): ?string
	{
		return $this->props['wp_plugin_name'] ?? null;
	}

	private function findPluginName(): ?string
	{
		return ! Val::isBlank($this->pluginHeaders) && isset($this->pluginHeaders['PluginName']) ?
			$this->pluginHeaders['PluginName'] :
			null;
	}

	public function getPluginDescription(): ?string
	{
		return $this->props['wp_plugin_description'] ?? null;
	}

	private function findPluginDescription(): ?string
	{
		return ! Val::isBlank($this->pluginHeaders) && isset($this->pluginHeaders['Description']) ?
			$this->pluginHeaders['Description'] :
			null;
	}

	/**
	 * The namespace is one that's assigned to the `app/` directory in the `psr-4`
	 * section of the `autoload` key in the `composer.json` file.
	 */
	public function getNamespace(): ?string
	{
		return $this->props['php_namespace'] ?? null;
	}

	private function findNamespace(): ?string
	{
		$namespace = $this->codex->getRootNamespace();

		if (Val::isBlank($namespace)) {
			return null;
		}

		return rtrim($namespace, '\\');
	}

	public function getVendorPrefix(): ?string
	{
		return $this->props['php_vendor_prefix'] ?? null;
	}

	private function findVendorPrefix(): ?string
	{
		$prefix = $this->codex->getConfig('scoper.prefix');

		return is_string($prefix) && ! Val::isBlank($prefix) ? $prefix : null;
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
	public function getPluginFile(): ?SplFileInfo
	{
		return $this->pluginFile;
	}

	private function findPluginFile(): ?SplFileInfo
	{
		$results = Finder::create()
			->ignoreVCS(true)
			->ignoreDotFiles(true)
			->files()
			->depth(0)
			->name('*.php')
			->contains('Plugin Name:')
			->sortByName()
			->in($this->codex->getProjectPath());

		foreach ($results as $item) {
			return $item;
		}

		return null;
	}

	/**
	 * Parse the plugin headers from the main plugin file.
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_file_data/
	 *
	 * @return array<string,string>|null The plugin headers mapped in array e.g. ['PluginName' => 'Plugin Name']
	 */
	private function parsePluginHeaders(): ?array
	{
		$file = $this->findPluginFile();

		if (Val::isBlank($file)) {
			return null;
		}

		$fileData = file_get_contents($file->getRealPath(), false, null, 0, 8 * 1024);

		if ($fileData === false) {
			$fileData = '';
		}

		// Make sure we catch CR-only line endings.
		$fileData = str_replace("\r", "\n", $fileData);
		$headers = [];

		foreach (
			[
				'PluginName' => 'Plugin Name',
				'Description' => 'Description',
			] as $field => $regex
		) {
			preg_match('/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . preg_quote($regex) . ':(.*)$/mi', $fileData, $match);

			if (isset($match[1])) {
				$headers[$field] = trim((string) preg_replace('/\s*(?:\*\/|\?>).*/', '', $match[1]));
			} else {
				$headers[$field] = '';
			}
		}

		return $headers;
	}
}
