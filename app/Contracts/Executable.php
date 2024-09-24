<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Contracts;

interface Executable
{
	public const SUCCESS = 0;

	public const FAILURE = 1;

	public const INVALID = 2;

	public function execute(): int;
}
