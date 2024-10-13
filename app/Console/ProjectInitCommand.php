<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Concerns\RunOnComposerEvent;
use Syntatis\Codex\Companion\Console\ProjectInitCommand\Howdy;
use Syntatis\Utils\Str;
use Syntatis\Utils\Val;

class ProjectInitCommand extends BaseCommand
{
	use RunOnComposerEvent;

	protected function configure(): void
	{
		$this->setName('project:init');
		$this->setDescription('Initialize a project');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$codex = new Codex($this->projectPath);
		$style = new SymfonyStyle($input, $output);
		$projectName = $codex->getProjectName();

		if (Val::isBlank($projectName)) {
			$style->warning('Project name not found.');

			return 0;
		}

		if (
			$projectName === 'syntatis/howdy' ||
			Str::startsWith($projectName, 'syntatis/howdy-')
		) {
			return (new Howdy($codex, $style))->execute();
		}

		$style->warning('Unsupported project: "' . $projectName . '".');

		return 0;
	}
}
