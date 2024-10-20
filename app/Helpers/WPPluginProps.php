<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers;

use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Utils\Str;
use Syntatis\Utils\Val;

use function file_get_contents;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function str_replace;
use function trim;

/**
 * Find and handle common properties of a WordPress plugin.
 *
 * @phpstan-type Props = array{
 * 		wp_plugin_name?:string|null,
 * 		wp_plugin_slug?:string|null,
 * 		wp_plugin_description?:string|null
 * }
 */
class WPPluginProps
{
	private Codex $codex;

	private ?SplFileInfo $pluginFile;

	/** @var array<string,string> */
	private ?array $pluginHeaders = null;

	/** @phpstan-var Props */
	private array $props = [];

	public function __construct(Codex $codex)
	{
		$this->codex = $codex;
		$this->pluginFile = $this->findPluginFile();
		$this->pluginHeaders = $this->parsePluginHeaders();
		$this->props = [
			'wp_plugin_name' => $this->findPluginName(),
			'wp_plugin_slug' => $this->findPluginSlug(),
		];

		$description = $this->findPluginDescription();

		if (Val::isBlank($description)) {
			return;
		}

		$this->props['wp_plugin_description'] = $description;
	}

	/** @phpstan-return Props */
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
	 * @return string|null The plugin name e.g. plugin-name, yoast-seo, etc.
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
