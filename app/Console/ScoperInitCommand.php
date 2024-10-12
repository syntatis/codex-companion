<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Console\Helpers\ShellProcess;
use Syntatis\Codex\Companion\Console\ScoperInitCommand\PrefixerProcess;
use Syntatis\Codex\Companion\Helpers\PHPScoperRequirement;
use Syntatis\Codex\Companion\Projects\Howdy\ProjectProps;
use Syntatis\Codex\Companion\Traits\RunOnComposerEvent;
use Syntatis\Utils\Val;

use function sprintf;

class ScoperInitCommand extends BaseCommand
{
	use RunOnComposerEvent;

	protected function configure(): void
	{
		$this->setName('scoper:init');
		$this->setDescription('Scope dependencies namespace with a prefix');
		$this->addOption('yes', 'y', null, 'Do not ask for confirmation');
		$this->addOption('no-dev', null, null, 'Skip installing packages listed in "require-dev"');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$codex = new Codex($this->projectPath);
		$style = new SymfonyStyle($input, $output);

		if (! ((bool) $input->getOption('yes') || $this->getConfirmation($codex, $style))) {
			$style->warning('The command has been aborted.');

			return 0;
		}

		$proc = (new ShellProcess($codex, $style))
			->withErrorMessage('Failed to scope the dependencies namespace')
			->run('composer bin php-scoper show -N');

		/**
		 * If an error occurred while listing all the available package installed,
		 * do not proceed. This list is required to verify whether the required
		 * packages, such as "humbug/php-scoper", is already installed.
		 *
		 * @see https://getcomposer.org/doc/03-cli.md#show-info
		 */
		if ($proc->isFailed()) {
			return $proc->getExitCode();
		}

		// If the required package is not installed, install it first.
		if (! (new PHPScoperRequirement($proc->getCurrent()->getOutput()))->isMet()) {
			$proc = (new ShellProcess($codex, $style))
				->withMessage('Installing <info>humbug/php-scoper</info>...')
				->run('composer bin php-scoper require -W humbug/php-scoper');

			if ($proc->isFailed()) {
				return $proc->getExitCode();
			}
		}

		$prefixer = new PrefixerProcess($codex);
		$prefixer->setDevMode(! (bool) $input->getOption('no-dev'));

		return $prefixer->execute($style);
	}

	private function getConfirmation(Codex $codex, StyleInterface $style): bool
	{
		$prefix = $codex->getProjectName() === 'syntatis/howdy' ?
			(new ProjectProps($codex))->getVendorPrefix() :
			null;

		$style->note(
			Val::isBlank($prefix) ?
			'This command will prefix the dependencies namespace' :
			sprintf('This command will prefix the dependencies namespace with "%s".', $prefix),
		);

		return $style->confirm('Do you want to proceed?', true);
	}
}
