<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VersionsUpdateCommand extends BaseCommand
{
	protected function configure(): void
	{
		$this->setName('versions:update');
		$this->setAliases(['v:update', 'ver:update']);
		$this->setDescription('Update the project versions.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		return 0;
	}
}
