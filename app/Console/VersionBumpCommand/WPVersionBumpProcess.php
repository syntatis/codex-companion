<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console\VersionBumpCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Filesystem\Path;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Concerns\RunProcess;
use Syntatis\Codex\Companion\Contracts\Executable;
use Syntatis\Codex\Companion\Projects\Howdy\VersioningFiles;
use Throwable;

use function sprintf;

class WPVersionBumpProcess implements Executable
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
	}

	public function execute(): int
	{
		try {
			$files = new VersioningFiles($this->codex);

			foreach ($files as $file) {
				$this->output->text(
					sprintf(
						'Updating version in <comment>%s</comment>',
						Path::makeRelative($file->getRealPath(), $this->codex->getProjectPath()),
					),
				);
			}
		} catch (Throwable $th) {
			$this->output->error($th->getMessage());

			return 1;
		}

		return 0;
	}
}
