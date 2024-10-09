<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Contracts;

use SplFileInfo;

interface EditableFile
{
	public function setFile(SplFileInfo $file): void;
}
