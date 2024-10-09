<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ToolsSetupCommand extends BaseCommand
{
	protected function configure(): void
	{
		$this->setName('tools:setup');
		$this->setDescription('Add and setup a project tool');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$style = new SymfonyStyle($input, $output);
		$style->note('This command is currently a work in progress.');
		$confirmed = $style->confirm('Do you want to proceed?', false);

		if (! $confirmed) {
			$style->warning('The command has been aborted.');

			return 0;
		}

		$default = '(none)'; // Default choice.
		$choice = $style->choice('Select the tool to setup', [
			'(none)',
			'phpstan',
			'phpunit',
			'vscode',
			'wp-env',
		], $default);

		return 0;
	}
}
