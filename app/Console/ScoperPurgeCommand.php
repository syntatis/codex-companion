<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Syntatis\Codex\Companion\Helpers\PHPScoperFilesystem;
use Throwable;

class ScoperPurgeCommand extends BaseCommand
{
	protected function configure(): void
	{
		$this->setName('scoper:purge');
		$this->setDescription('Delete all dependencies that have been scoped with a prefix');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$style = new SymfonyStyle($input, $output);
		$style->note('This command will delete all dependencies that have been scoped with a prefix.');
		$style->confirm('Do you want to proceed?', false);

		try {
			$scoperFilesystem = new PHPScoperFilesystem($this->codex);
			$scoperFilesystem->removeAll();

			$style->success('All scoped dependencies have been deleted.');

			return 0;
		} catch (Throwable $th) {
			$style->error($th->getMessage());

			return 1;
		}
	}
}
