<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Contracts;

interface VersionPatchIncrementable
{
	public function incrementPatch(): Versionable;
}
