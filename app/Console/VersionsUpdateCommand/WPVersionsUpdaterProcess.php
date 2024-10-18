<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console\VersionsUpdateCommand;

use RuntimeException;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Finder\Finder;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Concerns\RunProcess;
use Syntatis\Codex\Companion\Contracts\Executable;
use Syntatis\Codex\Companion\Projects\Howdy\ProjectProps;

class WPVersionsUpdaterProcess implements Executable
{
	use RunProcess;

	private Codex $codex;

	/**
	 * // phpcs:ignore
	 * @param StyleInterface&OutputInterface $output
	 */
	public function __construct(Codex $codex, $output)
	{
		$this->codex = $codex;
		$this->output = $output;

		foreach ($this->findFiles() as $file) {
			$this->output->text($file->getRealPath());
			// $this->updateVersion($file);
		}
	}

	/** @return iterable<SplFileInfo> */
	private function findFiles(): iterable
	{
		$finder = Finder::create()
			->in($this->codex->getProjectPath())
			->files()
			->name(['package.json', 'readme.txt']);

		foreach ($finder as $file) {
			yield $file;
		}

		/**
		 * Since it's supposed to be a WordPress plugin, reuse the `ProjectProps`
		 * from the `Howdy` project to find the plugin main file.
		 */
		$pluginFile = (new ProjectProps($this->codex))
			->getPluginFile();

		if (! ( $pluginFile instanceof SplFileInfo)) {
			throw new RuntimeException('Unable to find the plugin main file.');
		}

		yield $pluginFile;
	}

	public function execute(): int
	{
		return 0;
	}
}
