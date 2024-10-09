<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Projects\Howdy;

use Countable;
use IteratorAggregate;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Traversable;

/** @phpstan-implements IteratorAggregate<SplFileInfo> */
class ProjectFiles implements IteratorAggregate, Countable
{
	private Finder $finder;

	public function __construct(string $projectDir)
	{
		$this->finder = Finder::create()
			->files()
			->in($projectDir)
			->name('/(.*\.(php|json|js|jsx|ts|tsx|pot)|readme\.txt|phpcs\.xml(\.dist)?)$/')
			->notPath('/vendor|node_modules|(?<!xml\.)dist(.*)?|.*\.config.js|.*\-lock\.json/');
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
