<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Contracts;

use Stringable;

interface Versionable extends Stringable
{
	public function incrementMinor(): Versionable;

	public function incrementMajor(): Versionable;
}
