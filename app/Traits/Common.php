<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Traits;

use Composer\Factory;

use function dirname;

trait Common
{
	private string $projectRootDir = '';

	private static function getComposerFile(): string
	{
		return Factory::getComposerFile();
	}

	private static function getRootDir(): string
	{
		return dirname(self::getComposerFile());
	}
}
