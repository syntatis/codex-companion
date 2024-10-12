<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console\ScoperInitCommand;

use Symfony\Component\Console\Style\StyleInterface;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Concerns\RunProcess;
use Syntatis\Codex\Companion\Contracts\Executable;
use Syntatis\Codex\Companion\Helpers\PHPScoperFilesystem;
use Syntatis\Utils\Val;

use function is_string;
use function sprintf;

class PrefixerProcess implements Executable
{
	use RunProcess;

	protected Codex $codex;

	private bool $devMode = true;

	public function __construct(Codex $codex)
	{
		$this->codex = $codex;
	}

	public function setDevMode(bool $mode): self
	{
		$this->devMode = $mode;

		return $this;
	}

	public function execute(StyleInterface $style): int
	{
		$this->style = $style;

		$prefix = $this->codex->getConfig('scoper.prefix');

		if (! is_string($prefix) || Val::isBlank($prefix)) {
			$this->style->warning('Vendor prefix is not set in the configuration file.');

			return 0;
		}

		$filesystem = new PHPScoperFilesystem($this->codex);
		$filesystem->removeAll();
		$filesystem->dumpComposerFile();

		$proc = $this->process($this->codex->getProjectPath())
			->withMessage('Processing dependencies to scope...')
			->run(
				sprintf(
					'composer install --no-interaction --no-plugins --no-scripts --prefer-dist%s --working-dir=%s',
					$this->devMode ? '' : ' --no-dev',
					$filesystem->getBuildPath(),
				),
			);

		if ($proc->isSuccessful()) {
			$proc = $this->process($filesystem->getBuildPath())
				->withMessage(
					sprintf(
						'Prefixing dependencies namespace with <comment>%s</comment>...',
						$prefix,
					),
				)
				->run(
					sprintf(
						'%s add-prefix --force --quiet --config=%s --output-dir=%s',
						$filesystem->getBinPath(),
						$filesystem->getConfigPath(),
						$filesystem->getOutputPath(),
					),
				);
		}

		if ($proc->isSuccessful()) {
			$proc = $this->process($this->codex->getProjectPath())
				->withSuccessMessage('Dependencies namespace has been prefixed successfully')
				->run(
					sprintf(
						'composer dump -d %s',
						$filesystem->getOutputPath(),
					),
				);
		}

		$filesystem->removeBuildPath();

		return $proc->getExitCode();
	}
}
