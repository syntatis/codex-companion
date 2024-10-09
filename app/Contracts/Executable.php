<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Contracts;

use Symfony\Component\Console\Style\StyleInterface;

interface Executable
{
	public function execute(StyleInterface $style): int;
}
