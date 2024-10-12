<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Contracts;

interface Executable
{
	public function execute(): int;
}
