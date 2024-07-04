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

	private function asInfo(string $output): string
	{
		return sprintf('<info>%s</info>', $output);
	}

	private function comment(string $output): string
	{
		return $this->asComment($this->prefixed($output));
	}

	private function info(string $output): string
	{
		return $this->asInfo($this->prefixed($output));
	}

	private function prefixed(string $output): string
	{
		return sprintf('%s %s', trim($this->consoleOutputPrefix), $output);
	}
}
