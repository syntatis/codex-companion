<?php

declare(strict_types=1);

namespace Codex\Companion;

use Codex\Companion\Commands\ProjectCommand;
use Composer\Command\BaseCommand;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

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
