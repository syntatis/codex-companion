<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Projects\Howdy\VersioningFiles;

use SplFileInfo;
use Syntatis\Codex\Companion\Contracts\Dumpable;
use Syntatis\Codex\Companion\Contracts\EditableFile;

class MainPluginFile implements Dumpable, EditableFile
{
	private SplFileInfo $file;

	public function setFile(SplFileInfo $file): void
	{
		$this->file = $file;
	}

	public function dump(): void
	{
	}
}
