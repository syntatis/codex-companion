<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, Capable
{
	public function activate(Composer $composer, IOInterface $io): void
	{
	}

	public function deactivate(Composer $composer, IOInterface $io): void
	{
	}

	public function uninstall(Composer $composer, IOInterface $io): void
	{
	}

	// phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
	public function getCapabilities(): array
	{
		return ['Composer\Plugin\Capability\CommandProvider' => 'Syntatis\ComposerProjectPlugin\PluginCommands'];
	}
}
