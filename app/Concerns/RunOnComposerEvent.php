<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Concerns;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function dirname;

trait RunOnComposerEvent
{
	public static function executeOnComposer(string $vendorDir, InputInterface $input, OutputInterface $output): void
	{
		$command = new self(dirname($vendorDir));

		/**
		 * Pass the command definition to the input instance to avoid the following
		 * error.
		 *
		 * "The "--yes" option does not exist.".
		 */
		$input->bind($command->getDefinition());
		$command->execute($input, $output);
	}
}
