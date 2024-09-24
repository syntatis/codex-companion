<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion;

use Composer\Command\BaseCommand;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Syntatis\Codex\Companion\Commands\ProjectCommand;

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
