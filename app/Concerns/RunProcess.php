<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Concerns;

use Symfony\Component\Console\Style\StyleInterface;
use Syntatis\Codex\Companion\Console\Helpers\ShellProcess;

trait RunProcess
{
	protected StyleInterface $style;

	protected function process(?string $cwd = null): ShellProcess
	{
		return new ShellProcess($this->style, $cwd);
	}
}
