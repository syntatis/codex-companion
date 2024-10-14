<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Concerns\RunOnComposerEvent;
use Syntatis\Codex\Companion\Concerns\RunProcess;
use Syntatis\Codex\Companion\Console\ScoperInitCommand\PrefixerProcess;
use Syntatis\Codex\Companion\Helpers\PHPScoperRequirement;
use Syntatis\Utils\Val;

use function is_string;
use function sprintf;

class ScoperInitCommand extends BaseCommand
{
	use RunOnComposerEvent;
	use RunProcess;

	protected Codex $codex;

	protected InputInterface $input;

	protected function configure(): void
	{
		$this->setName('scoper:init');
		$this->setDescription('Scope dependencies namespace with a prefix');
		$this->addOption('yes', 'y', null, 'Do not ask for confirmation');
		$this->addOption('no-dev', null, null, 'Skip installing packages listed in "require-dev"');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->input = $input;
		$this->output = new SymfonyStyle($input, $output);
		$this->codex = new Codex($this->projectPath);

		if (! ((bool) $input->getOption('yes') || $this->getConfirmation())) {
			$this->output->warning('The command has been aborted.');

			return 0;
		}

		$proc = $this->process($this->codex->getProjectPath())
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
			$proc = $this->process($this->codex->getProjectPath())
				->withMessage('Installing <info>humbug/php-scoper</info>...')
				->run('composer bin php-scoper require -W humbug/php-scoper');

			if ($proc->isFailed()) {
				return $proc->getExitCode();
			}
		}

		return (new PrefixerProcess($this->codex, $this->output))
			->setDevMode(! (bool) $input->getOption('no-dev'))
			->execute();
	}

	private function getConfirmation(): bool
	{
		$this->output->note($this->getConfirmationNote());

		return $this->output->confirm('Do you want to proceed?', true);
	}

	private function getConfirmationNote(): string
	{
		$noDev = (bool) $this->input->getOption('no-dev');
		$prefix = $this->codex->getConfig('scoper.prefix');

		if (! is_string($prefix) || Val::isBlank($prefix)) {
			$message = 'This command will prefix the dependencies namespace.';

			if ($noDev) {
				return <<<MESSAGE
				$message
				The packages listed in "install-dev" will be skipped.
				MESSAGE;
			}

			return $message;
		}

		if ($noDev) {
			return sprintf(
				'This command will prefix the dependencies namespace with "%s".',
				$prefix,
			);
		}

		return <<<MESSAGE
		This command will prefix the dependencies namespace with "$prefix".
		The packages listed in "install-dev" will be skipped.
		MESSAGE;
	}
}
