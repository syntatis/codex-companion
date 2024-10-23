<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Projects\Howdy;

use RuntimeException;
use SplFileInfo;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Helpers\WPPluginProps;
use Syntatis\Utils\Val;
use Syntatis\WPPluginReadMeParser\Parser;

use function is_file;
use function is_readable;

/**
 * Parse and retrieve the current versions of the WordPress plugin.
 *
 * @see https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/
 *
 * @phpstan-type Props = array{
 *      wp_version:non-empty-string,
 *      wp_tested:non-empty-string,
 *      wp_requires_min?:string|null,
 *      wp_requires_php?:string|null,
 * }
 */
class VersioningProps
{
	private Codex $codex;

	private WPPluginProps $wpPluginProps;

	/** @phpstan-var Props */
	private array $props;

	/**
	 * File object of the WordPress plugin main file.
	 */
	private SplFileInfo $pluginFile;

	private Parser $wpReadmeParser;

	public function __construct(Codex $codex)
	{
		$this->codex = $codex;
		$this->wpPluginProps = new WPPluginProps($codex);

		$readmeFile = $this->codex->getProjectPath('readme.txt');

		if (! is_file($readmeFile) || ! is_readable($readmeFile)) {
			throw new RuntimeException('Unable to read the "readme.txt" file.');
		}

		$pluginFile = $this->wpPluginProps->getFile();

		if (! $pluginFile instanceof SplFileInfo) {
			throw new RuntimeException('Unable to find the WordPress plugin main file.');
		}

		$this->pluginFile = $pluginFile;
		$this->wpReadmeParser = new Parser($readmeFile);
		$this->props = [
			'wp_version' => $this->findVersion(),
			'wp_tested' => $this->findTested(),
		];
	}

	/** @phpstan-return Props */
	public function get(): array
	{
		return $this->props;
	}

	/**
	 * Find the current version (or Stable tag) of the WordPress plugin.
	 *
	 * @phpstan-return non-empty-string
	 */
	private function findVersion(): string
	{
		$version = $this->wpReadmeParser->stable_tag; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- WordPress conventions

		if (Val::isBlank($version)) {
			throw new RuntimeException('Unable to find the "Stable tag" in the "readme.txt" file.');
		}

		return $version;
	}

	/**
	 * Find the current version of WordPress that the plugin has been tested up to.
	 *
	 * @phpstan-return non-empty-string
	 */
	private function findTested(): string
	{
		$tested = $this->wpReadmeParser->tested;

		if (Val::isBlank($tested)) {
			throw new RuntimeException('Unable to find the "Tested up to" in the "readme.txt" file.');
		}

		return $tested;
	}
}
