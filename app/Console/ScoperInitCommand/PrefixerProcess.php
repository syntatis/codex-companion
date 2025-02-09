<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console\ScoperInitCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Concerns\RunProcess;
use Syntatis\Codex\Companion\Contracts\Executable;
use Syntatis\Codex\Companion\Helpers\PHPScoperFilesystem;
use Syntatis\Utils\Val;

use function array_merge;
use function file_exists;
use function is_string;
use function sprintf;

class PrefixerProcess implements Executable
{
	use RunProcess;

	protected Codex $codex;

	/** @var StyleInterface&OutputInterface */
	protected $output;

	private bool $devMode = true;

	/**
	 * // phpcs:ignore
	 * @param StyleInterface&OutputInterface $output
	 */
	public function __construct(Codex $codex, $output)
	{
		$this->codex = $codex;
		$this->output = $output;
	}

	public function setDevMode(bool $mode): self
	{
		$this->devMode = $mode;

		return $this;
	}

	public function execute(): int
	{
		$prefix = $this->codex->getConfig('scoper.prefix');

		if (! is_string($prefix) || Val::isBlank($prefix)) {
			$this->output->warning('Vendor prefix is not set in the configuration file.');

			return 0;
		}

		$filesystem = new PHPScoperFilesystem($this->codex);
		$filesystem->removeAll();
		$filesystem->dumpComposerFile();

		$processDepsArgs = [
			'composer',
			'install',
			'--no-interaction',
			'--no-plugins',
			'--no-scripts',
			'--prefer-dist',
		];

		if (! $this->devMode) {
			$processDepsArgs = array_merge(
				$processDepsArgs,
				['--no-dev'],
			);
		}

		$proc = $this->process($filesystem->getBuildPath())
			->withMessage('Processing dependencies to scope...')
			->run($processDepsArgs);

		if ($proc->isSuccessful()) {
			if (! file_exists($filesystem->getBinPath())) {
				$this->output->error('Unable to locate the PHP-Scoper binary.');

				return 1;
			}

			$proc = $this->process($filesystem->getBuildPath())
				->withMessage(
					sprintf(
						'Prefixing dependencies namespace with <comment>%s</comment>...',
						$prefix,
					),
				)
				->run(
					[
						$filesystem->getBinPath(),
						'add-prefix',
						'--force',
						sprintf('--config=%s', $filesystem->getConfigPath()),
						sprintf('--output-dir=%s', $filesystem->getOutputPath()),
					],
				);
		}

		if ($proc->isSuccessful()) {
			$proc = $this->process($filesystem->getOutputPath())
				->withSuccessMessage('Dependencies namespace has been prefixed successfully')
				->run(['composer', 'dump']);
		}

		$filesystem->removeBuildPath();

		return $proc->getExitCode();
	}
}
