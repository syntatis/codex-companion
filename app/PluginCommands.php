<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin;

use Composer\Command\BaseCommand;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Syntatis\ComposerProjectPlugin\Commands\ProjectCommand;

class PluginCommands implements CommandProviderCapability
{
	/**
	 * Get the commands provided by this plugin.
	 *
	 * @return array<BaseCommand> The list of commands provided by this plugin.
	 */
	public function getCommands(): array
	{
		return [new ProjectCommand()];
	}
}
