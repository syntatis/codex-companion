<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin\Actions\Initializers\WPStarterPlugin;

use Countable;
use IteratorAggregate;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Traversable;

use function dirname;

/** @phpstan-implements IteratorAggregate<SplFileInfo> */
class ProjectFiles implements IteratorAggregate, Countable
{
	private Finder $finder;

	private string $rootDir;

	public function __construct(SplFileInfo $composerJsonInfo)
	{
		$composerJsonFile = $composerJsonInfo->getRealPath();
		$rootDir = dirname($composerJsonFile);
		$finder = Finder::create()
			->files()
			->in($rootDir)
			->name('/(.*\.(php|json|js|pot)|readme\.txt|phpcs\.xml(\.dist)?)$/')
			->notPath('/vendor|node_modules|(?<!xml\.)dist(.*)?|.*\.config.js|.*\-lock\.json/');
		$this->rootDir = $rootDir;
		$this->finder = $finder;
	}

	public function getRootDirectory(): string
	{
		return $this->rootDir;
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
