<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Projects\Howdy;

use Countable;
use IteratorAggregate;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Helpers\WPPluginProps;
use Traversable;

use function count;

/**
 * Handle version updates in some files that may contains version number,
 * such as the plugin main file, phpcs config, and readme file.
 *
 * @phpstan-implements IteratorAggregate<SplFileInfo>
 */
class VersioningFiles implements IteratorAggregate, Countable
{
	private Codex $codex;

	/** @var Traversable<SplFileInfo>&Countable */
	private $files;

	private array $props;

	public function __construct(Codex $codex)
	{
		$this->codex = $codex;
		$this->files = $this->findFiles();
	}

	public function count(): int
	{
		return count($this->files);
	}

	/** @return Traversable<SplFileInfo> */
	public function getIterator(): Traversable
	{
		foreach ($this->files as $file) {
			yield $file;
		}
	}

	/**
	 * Find files that may contains version number.
	 *
	 * @return Traversable<SplFileInfo>&Countable
	 */
	private function findFiles(): iterable
	{
		$props = new WPPluginProps($this->codex);
		$results = Finder::create()
			->in($this->codex->getProjectPath())
			->name('package.php')
			->append([$props->getFile()]);

		foreach ($results as $result) {
			yield $result;
		}
	}
}
