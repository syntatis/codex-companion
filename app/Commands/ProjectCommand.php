<?php

declare(strict_types=1);

namespace Codex\Companion\Commands;

use Codex\Companion\Actions\Initialize;
use Composer\Command\BaseCommand;
use Composer\Composer;
use Composer\IO\ConsoleIO;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function version_compare;

class ProjectCommand extends BaseCommand
{
	protected function configure(): void
	{
		$this->setName('codex:project')
			->setDescription('Commands to manage project')
			->setHelp(<<<'EOT'
			The <info>codex:project</info> command allows you to manage project
			in the current directory. The command depends on the
			<info>codex</info> to be provided as an <info>extra</info> data in
			the composer.json file.
			EOT)
			->addArgument('action', InputArgument::REQUIRED, 'The action to execute on the project')
			->addUsage('init');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		// @phpstan-ignore-next-line -- Version compare is needed to determine the correct method to call
		$composer = version_compare(Composer::VERSION, '2.3.0', '<') ?
			// @phpstan-ignore-next-line
			$this->getComposer() :
			$this->requireComposer();
		$io = $this->getIO();

		if (! $io instanceof ConsoleIO) {
			throw new RuntimeException('Unable to run outside a console environment.');
		}

		$action = $input->getArgument('action');

		switch ($action) {
			case 'init':
				return (new Initialize($composer, $io))->execute();

			default:
				throw new InvalidArgumentException('Invalid action: ' . $action);
		}
	}
}
