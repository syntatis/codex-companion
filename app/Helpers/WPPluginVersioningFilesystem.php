<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers;

use Symfony\Component\Finder\Finder;
use Syntatis\Codex\Companion\Codex;

class WPPluginVersioningFilesystem
{
	private Codex $codex;

	private const FILE_AND_PATTERNS = [];

	public function __construct(Codex $codex)
	{
		$this->codex = $codex;
	}

	/**
	 * Bump the version of the WordPress plugin.
	 */
	public function bump(string $label, string $version): void
	{
	}

	private function getFiles(): iterable
	{
		return Finder::create()
			->files()
			->in($this->codex->getProjectPath())
			->name(self::getPatterns());
	}

	private static function getPatterns(): array
	{
		return self::FILE_AND_PATTERNS;
	}
}
