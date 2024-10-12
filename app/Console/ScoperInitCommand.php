<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Syntatis\Codex\Companion\Console\Helpers\ShellProcess;
use Syntatis\Codex\Companion\Console\ScoperInitCommand\PrefixProcessor;
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
		$style = new SymfonyStyle($input, $output);

		if (! ((bool) $input->getOption('yes') || $this->getConfirmation($style))) {
			$style->warning('The command has been aborted.');

			return 0;
		}

		$process = (new ShellProcess($this->codex, $style))
			->withErrorMessage('Failed to scope the dependencies namespace')
			->run('composer bin php-scoper show -N');

		/**
		 * If an error occurred while listing all the available package installed,
		 * do not proceed. This list is required to verify whether the required
		 * packages, such as "humbug/php-scoper", is already installed.
		 *
		 * @see https://getcomposer.org/doc/03-cli.md#show-info
		 */
		if ($process->isFailed()) {
			return $process->getExitCode();
		}

		// If the required package is not installed, install it first.
		if (! (new PHPScoperRequirement($process->getCurrent()->getOutput()))->isMet()) {
			$process = (new ShellProcess($this->codex, $style))
				->withMessage(sprintf('Installing <info>%s</info>...', 'humbug/php-scoper'))
				->run('composer bin php-scoper require -W humbug/php-scoper');

			if ($process->isFailed()) {
				return $process->getExitCode();
			}
		}

		$prefixProcessor = new PrefixProcessor($this->codex);
		$prefixProcessor->setDevMode(! (bool) $input->getOption('no-dev'));

		return $prefixProcessor->execute($style);
	}

	private function getConfirmation(StyleInterface $style): bool
	{
		$prefix = $this->codex->getProjectName() === 'syntatis/howdy' ?
			(new ProjectProps($this->codex))->getVendorPrefix() :
			null;

		$style->note(
			Val::isBlank($prefix) ?
			'This command will prefix the dependencies namespace' :
			sprintf('This command will prefix the dependencies namespace with "%s".', $prefix),
		);

		return $style->confirm('Do you want to proceed?', true);
	}
}
