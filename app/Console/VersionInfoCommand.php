<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Console\VersionInfoCommand\WPVersionInfoProcess;

class VersionInfoCommand extends BaseCommand
{
	protected function configure(): void
	{
		$this->setName('version:info');
		$this->setAliases(['v:info', 'ver:info']);
		$this->setDescription('Retrieve versions of the current project.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$codex = new Codex($this->projectPath);
		$style = new SymfonyStyle($input, $output);
		$type = $codex->getComposer('type');

		if ($type === 'wordpress-plugin') {
			return (new WPVersionInfoProcess($codex, $style))->execute();
		}

		$style->warning('Unsupported project type.');

		return 0;
	}
}
