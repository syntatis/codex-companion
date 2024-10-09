<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Projects\Howdy;

use SplFileInfo;
use Syntatis\Codex\Companion\Contracts\Dumpable;
use Syntatis\Codex\Companion\Contracts\EditableFile;
use Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles\CommonFiles;
use Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles\ComposerFile;
use Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles\PackageFile;
use Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles\PHPCSFile;
use Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles\POTFile;
use Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles\ReadmeTxtFile;
use Syntatis\Utils\Str;

use function array_merge;

/**
 * This class is designed to initialize a fresh new "howdy" project.
 *
 * @phpstan-type ValidatedItems array{
 * 		php_vendor_prefix:non-empty-string,
 * 		php_namespace:non-empty-string,
 * 		wp_plugin_name:non-empty-string,
 * 		wp_plugin_slug:non-empty-string,
 * 		wp_plugin_description?:non-empty-string
 * }
 * @phpstan-type SearchReplaceItems array{
 * 		php_vendor_prefix:non-empty-string,
 * 		php_namespace:non-empty-string,
 * 		wp_plugin_name:non-empty-string,
 * 		wp_plugin_slug:non-empty-string,
 * 		wp_plugin_description?:non-empty-string,
 *		camelcase:string,
 *		kebabcase:string,
 *		snakecase:string
 * }
 */
class InitializeFiles
{
	/** @phpstan-var SearchReplaceItems */
	private array $searches;

	/** @phpstan-var SearchReplaceItems */
	private array $replacements;

	/**
	 * @phpstan-param ValidatedItems $props
	 * @phpstan-param ValidatedItems $inputs
	 */
	public function __construct(array $props, array $inputs)
	{
		$this->searches = array_merge(
			$props,
			[
				/**
				 * Various letter case.
				 *
				 * The kebabcase, snakecase, and camelcase are various letter cases, that
				 * may be added for variables, string ids, etc.
				 */
				'camelcase' => Str::toCamelCase($props['wp_plugin_slug']),
				'kebabcase' => Str::toKebabCase($props['wp_plugin_slug']),
				'snakecase' => Str::toSnakeCase($props['wp_plugin_slug']),
			],
		);

		$this->replacements = array_merge(
			$inputs,
			[
				'camelcase' => Str::toCamelCase($inputs['wp_plugin_slug']),
				'kebabcase' => Str::toKebabCase($inputs['wp_plugin_slug']),
				'snakecase' => Str::toSnakeCase($inputs['wp_plugin_slug']),
			],
		);
	}

	public function file(SplFileInfo $fileInfo): void
	{
		if (! $fileInfo->isFile()) {
			return;
		}

		$handler = $this->getHandler($fileInfo->getBasename());

		if ($handler instanceof EditableFile) {
			$handler->setFile($fileInfo);
		}

		if (! ($handler instanceof Dumpable)) {
			return;
		}

		$handler->dump();
	}

	/** @return Dumpable|EditableFile */
	private function getHandler(string $filename)
	{
		switch ($filename) {
			case 'plugin-name.pot':
				return new POTFile($this->searches, $this->replacements);

			case 'composer.json':
				return new ComposerFile($this->searches, $this->replacements);

			case 'package.json':
				return new PackageFile($this->searches, $this->replacements);

			case 'phpcs.xml':
			case 'phpcs.xml.dist':
				return new PHPCSFile($this->searches, $this->replacements);

			case 'readme.txt':
				return new ReadmeTxtFile($this->searches, $this->replacements);

			default:
				return new CommonFiles($this->searches, $this->replacements);
		}
	}
}
