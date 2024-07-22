<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin\Actions\Initializers\WPStarterPlugin;

use Composer\IO\ConsoleIO;
use Syntatis\ComposerProjectPlugin\Helpers\PHPNamespace;
use Syntatis\ComposerProjectPlugin\Helpers\ProjectName;
use Syntatis\ComposerProjectPlugin\Helpers\VendorPrefix;
use Syntatis\ComposerProjectPlugin\Helpers\WPPluginName;
use Syntatis\ComposerProjectPlugin\Helpers\WPPluginSlug;
use Syntatis\ComposerProjectPlugin\Traits\ConsoleOutput;

use function str_replace;
use function Syntatis\Utils\is_blank;
use function Syntatis\Utils\kebabcased;

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
		/** @var ProjectName $projectName */
		$projectName = $this->io->askAndValidate(
			$this->prefixed('Project name: '),
			fn ($name) => new ProjectName($name, $this->consoleOutputPrefix),
			3,
			'',
		);

		$defaultPluginSlug = kebabcased(str_replace('/', '-', (string) $projectName));

		/** @var WPPluginSlug $wpPluginSlug */
		$wpPluginSlug = $this->io->askAndValidate(
			$this->prefixed('Plugin slug (optional) [' . $this->asComment($defaultPluginSlug) . ']: '),
			fn ($slug) => new WPPluginSlug($slug, $this->consoleOutputPrefix),
			2,
			$defaultPluginSlug,
		);

		$defaultPluginName = $wpPluginSlug->toPluginName();

		/** @var WPPluginName $wpPluginName */
		$wpPluginName = $this->io->askAndValidate(
			$this->prefixed('Plugin name (optional) [' . $this->asComment($defaultPluginName) . ']: '),
			fn ($slug) => new WPPluginName($slug, $this->consoleOutputPrefix),
			2,
			$defaultPluginName,
		);

		$defaultPHPNamespace = $projectName->toNamespace();

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
			'project_name' => (string) $projectName,
			'wp_plugin_name' => (string) $wpPluginName,
			'wp_plugin_slug' => (string) $wpPluginSlug,
		];
	}

	/**
	 * @return array<string,string>|string|null
	 *
	 * @phpstan-param non-empty-string|null $offset
	 * @phpstan-return ($offset is non-empty-string ? string|null : array<string,string>)
	 */
	public function get(?string $offset = null)
	{
		if (is_blank($offset)) {
			return $this->inputs;
		}

		return $this->inputs[$offset] ?? null;
	}
}
