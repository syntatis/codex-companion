<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Traits;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Syntatis\Codex\Companion\Codex;

trait RunOnComposerEvent
{
	public static function executeOnComposer(?Codex $codex, InputInterface $input, OutputInterface $output): void
	{
		if (! $codex instanceof Codex) {
			return;
		}

		$command = new self($codex);

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
