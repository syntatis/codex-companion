<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Projects\Howdy;

use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Helpers\WPPluginProps;
use Syntatis\Utils\Val;

use function array_merge;
use function is_string;
use function rtrim;

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
 *
 * @phpstan-type Props = array{
 * 		php_vendor_prefix?:string|null,
 * 		php_namespace?:string|null,
 * 		wp_plugin_name?:string|null,
 * 		wp_plugin_slug?:string|null,
 * 		wp_plugin_description?:string|null,
 * }
 */
class ProjectProps
{
	private Codex $codex;

	/** @phpstan-var Props */
	private array $props = [];

	public function __construct(Codex $codex)
	{
		$this->codex = $codex;
		$this->props = array_merge(
			[
				'php_vendor_prefix' => $this->findVendorPrefix(),
				'php_namespace' => $this->findNamespace(),
			],
			(new WPPluginProps($codex))->get(),
		);
	}

	/** @phpstan-return Props */
	public function get(): array
	{
		return $this->props;
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
}
