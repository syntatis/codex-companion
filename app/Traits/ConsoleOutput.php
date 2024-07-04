<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin\Traits;

use Composer\IO\ConsoleIO;

use function sprintf;
use function trim;

trait ConsoleOutput
{
	private string $consoleOutputPrefix = '';

	private ConsoleIO $io;

	private function asComment(string $output): string
	{
		return sprintf('<comment>%s</comment>', $output);
	}

	private function comment(string $output): string
	{
		return $this->prefixed($this->asComment($output));
	}

	private function prefixed(string $output): string
	{
		return sprintf('%s %s', trim($this->consoleOutputPrefix), $output);
	}
}
