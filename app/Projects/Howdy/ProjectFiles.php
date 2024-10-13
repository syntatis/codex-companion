<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Projects\Howdy;

use Countable;
use IteratorAggregate;
use SplFileInfo;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Utils\Val;
use Traversable;

use function is_string;
use function preg_quote;

/** @phpstan-implements IteratorAggregate<SplFileInfo> */
class ProjectFiles implements IteratorAggregate, Countable
{
	private Finder $finder;

	public function __construct(string $projectDir)
	{
		$codex = new Codex($projectDir);
		$projectDir = $codex->getProjectPath();
		$outputDir = $codex->getConfig('scoper.output-dir');

		$finder = Finder::create()
			->files()
			->in($projectDir)
			->name('/(.*\.(php|json|js|jsx|ts|tsx|pot)|readme\.txt|phpcs\.xml(\.dist)?)$/')
			->notPath('/vendor|node_modules|(?<!xml\.)dist(.*)?|.*\.config.js|.*\-lock\.json/');

		if (is_string($outputDir) && ! Val::isBlank($outputDir)) {
			$outputDir = Path::makeRelative($outputDir, $projectDir);
			$finder->notPath('/' . preg_quote($outputDir, '/') . '/');
		}

		$this->finder = $finder;
	}

	public function count(): int
	{
		return $this->finder->count();
	}

	/** @return Traversable<SplFileInfo> */
	public function getIterator(): Traversable
	{
		return $this->finder;
	}
}
