<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console\ProjectInitCommand\Howdy;

use Symfony\Component\Console\Style\StyleInterface;
use Syntatis\Codex\Companion\Contracts\Executable;
use Syntatis\Codex\Companion\Exceptions\MissingRequiredInfo;
use Syntatis\Codex\Companion\Helpers\PHPNamespace;
use Syntatis\Codex\Companion\Helpers\PHPVendorPrefix;
use Syntatis\Codex\Companion\Helpers\WPPluginDescription;
use Syntatis\Codex\Companion\Helpers\WPPluginName;
use Syntatis\Codex\Companion\Helpers\WPPluginSlug;
use Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles;
use Syntatis\Utils\Str;
use Syntatis\Utils\Val;

use function array_filter;
use function array_keys;
use function count;
use function in_array;
use function is_string;

use const ARRAY_FILTER_USE_BOTH;

/** @phpstan-import-type ValidatedItems from InitializeFiles */
class UserInputPrompts implements Executable
{
	private StyleInterface $style;

	/** @phpstan-var ValidatedItems */
	private array $inputs;

	/** @phpstan-var ValidatedItems */
	private array $props;

	/**
	 * @param array<string,string|null> $props The list of properties from the "howdy" project.
	 * @param StyleInterface            $style Output for the console to interact with the user.
	 */
	public function __construct(array $props, StyleInterface $style)
	{
		$missing = [];

		if (! $this->evalProjectProps($props, $missing)) {
			throw new MissingRequiredInfo(...$missing);
		}

		$this->props = $props;
		$this->style = $style;
	}

	/**
	 * @param array<string,string|null> $props
	 * @param array<int,string>         $missing
	 *
	 * @phpstan-assert-if-true ValidatedItems $props
	 */
	private function evalProjectProps(array $props, array &$missing): bool
	{
		$required = [
			'php_vendor_prefix',
			'php_namespace',
			'wp_plugin_name',
			'wp_plugin_slug',
		];

		$missing = array_keys(
			array_filter(
				[
					'php_vendor_prefix' => $props['php_vendor_prefix'] ?? null,
					'php_namespace' => $props['php_namespace'] ?? null,
					'wp_plugin_name' => $props['wp_plugin_name'] ?? null,
					'wp_plugin_slug' => $props['wp_plugin_slug'] ?? null,
				],
				static fn ($val, $key) => in_array($key, $required, true) && (! is_string($val) || Val::isBlank($val)),
				ARRAY_FILTER_USE_BOTH,
			),
		);

		return count($missing) <= 0;
	}

	public function execute(): int
	{
		$this->style->text([
			'To get started with your new WordPress plugin project, please provide the',
			'Plugin slug. The Plugin slug should be in all-lowercase and use hyphens',
			'to separate words e.g. <comment>acme-awesome-plugin</comment>.',
		]);

		/** @var WPPluginSlug $wpPluginSlug */
		$wpPluginSlug = $this->style->ask(
			'Plugin slug',
			null,
			static fn ($value) => new WPPluginSlug(is_string($value) ? $value : ''),
		);

		/** @var WPPluginName $wpPluginName */
		$wpPluginName = $this->style->ask(
			'Plugin name',
			Str::toTitleCase((string) $wpPluginSlug),
			static fn ($value) => new WPPluginName(is_string($value) ? $value : ''),
		);

		/** @var PHPNamespace $phpNamespace */
		$phpNamespace = $this->style->ask(
			'PHP namespace',
			Str::toPascalCase((string) $wpPluginSlug),
			static fn ($value) => new PHPNamespace(is_string($value) ? $value : ''),
		);

		/** @var PHPVendorPrefix $vendorPrefix */
		$vendorPrefix = $this->style->ask(
			'PHP vendor prefix',
			Str::toPascalCase((string) $wpPluginSlug) . '\\Vendor',
			static fn ($value) => new PHPVendorPrefix(is_string($value) ? $value : ''),
		);

		/**
		 * Each of the values below are already validated when the users input their values.
		 * Users are not allowed to provide empty values. Otherwise, the class will throw
		 * them an error exception, and ask them to provide a valid value.
		 */
		$inputs = [
			'php_vendor_prefix' => (string) $vendorPrefix,
			'php_namespace' => (string) $phpNamespace,
			'wp_plugin_name' => (string) $wpPluginName,
			'wp_plugin_slug' => (string) $wpPluginSlug,
		];

		$this->inputs = $this->promptOptionals($inputs);

		return 0;
	}

	/** @phpstan-return ValidatedItems */
	public function getInputs(): array
	{
		return $this->inputs;
	}

	/** @phpstan-return ValidatedItems */
	public function getProjectProps(): array
	{
		return $this->props;
	}

	/**
	 * @phpstan-param ValidatedItems $inputs
	 *
	 * @phpstan-return ValidatedItems
	 */
	private function promptOptionals(array $inputs): array
	{
		// Description is an optional prop.
		if (isset($this->props['wp_plugin_description']) && ! Val::isBlank($this->props['wp_plugin_description'])) {
			/** @var WPPluginDescription $wpPluginDesc */
			$wpPluginDesc = $this->style->ask(
				'Plugin description',
				$this->props['wp_plugin_description'],
				static fn ($value) => new WPPluginDescription(is_string($value) ? $value : ''),
			);
			$wpPluginDesc = (string) $wpPluginDesc;

			if (! Val::isBlank($wpPluginDesc)) {
				$inputs['wp_plugin_description'] = $wpPluginDesc;
			}
		}

		return $inputs;
	}
}
