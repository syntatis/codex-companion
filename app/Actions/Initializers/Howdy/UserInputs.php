<?php

declare(strict_types=1);

namespace Codex\Companion\Actions\Initializers\Howdy;

use Codex\Companion\Helpers\PHPNamespace;
use Codex\Companion\Helpers\VendorPrefix;
use Codex\Companion\Helpers\WPPluginName;
use Codex\Companion\Helpers\WPPluginSlug;
use Codex\Companion\Traits\ConsoleOutput;
use Composer\IO\ConsoleIO;
use Syntatis\Utils\Val;

class UserInputs
{
	use ConsoleOutput;

	private ConsoleIO $io;

	/** @var array<string, string> */
	private array $inputs = [];

	public function __construct(
		ConsoleIO $io,
		string $ioPrefix
	) {
		$this->io = $io;
		$this->consoleOutputPrefix = $ioPrefix;
		$this->prompt();
	}

	private function prompt(): void
	{
		/** @var WPPluginSlug $wpPluginSlug */
		$wpPluginSlug = $this->io->askAndValidate(
			$this->prefixed('Plugin slug: '),
			fn ($slug) => new WPPluginSlug($slug, $this->consoleOutputPrefix),
			3,
			'',
		);

		$defaultPluginName = $wpPluginSlug->toPluginName();

		/** @var WPPluginName $wpPluginName */
		$wpPluginName = $this->io->askAndValidate(
			$this->prefixed('Plugin name (optional) [' . $this->asComment($defaultPluginName) . ']: '),
			fn ($slug) => new WPPluginName($slug, $this->consoleOutputPrefix),
			2,
			$defaultPluginName,
		);

		$defaultPHPNamespace = $wpPluginSlug->toNamespace();

		/** @var PHPNamespace $phpNamespace */
		$phpNamespace = $this->io->askAndValidate(
			$this->prefixed('PHP namespace (optional) [' . $this->asComment($defaultPHPNamespace) . ']: '),
			fn ($namespace) => new PHPNamespace($namespace, $this->consoleOutputPrefix),
			2,
			$defaultPHPNamespace,
		);

		$defaultVendorPrefix = $defaultPHPNamespace . '\\Vendor';

		/** @var VendorPrefix $vendorPrefix */
		$vendorPrefix = $this->io->askAndValidate(
			$this->prefixed('Vendor prefix (optional) [' . $this->asComment($defaultVendorPrefix) . ']: '),
			fn ($namespace) => new VendorPrefix($namespace, $this->consoleOutputPrefix),
			2,
			$defaultVendorPrefix,
		);

		$this->inputs = [
			'vendor_prefix' => (string) $vendorPrefix,
			'php_namespace' => (string) $phpNamespace,
			'wp_plugin_name' => (string) $wpPluginName,
			'wp_plugin_slug' => (string) $wpPluginSlug,
		];
	}

	/**
	 * @phpstan-param non-empty-string|null $offset
	 *
	 * @return array<string,string>|string|null
	 * @phpstan-return ($offset is non-empty-string ? string|null : array<string,string>)
	 */
	public function get(?string $offset = null)
	{
		if (Val::isBlank($offset)) {
			return $this->inputs;
		}

		return $this->inputs[$offset] ?? null;
	}
}
