<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console\ScoperInitCommand;

use Symfony\Component\Console\Style\StyleInterface;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Console\Helpers\ShellProcess;
use Syntatis\Codex\Companion\Contracts\Executable;
use Syntatis\Codex\Companion\Helpers\PHPScoperFilesystem;
use Syntatis\Codex\Companion\Projects\Howdy\ProjectProps;
use Syntatis\Utils\Val;

use function sprintf;

class PrefixProcessor implements Executable
{
	protected Codex $codex;

	private bool $devMode = true;

	public function __construct(Codex $codex)
	{
		$this->codex = $codex;
	}

	public function setDevMode(bool $mode): void
	{
		$this->devMode = $mode;
	}

	public function execute(StyleInterface $style): int
	{
		$projectProps = new ProjectProps($this->codex);
		$prefix = $projectProps->getVendorPrefix();

		if (Val::isBlank($prefix)) {
			$style->warning('Vendor prefix is not set in the configuration file.');

			return 0;
		}

		$filesystem = new PHPScoperFilesystem($this->codex);
		$filesystem->removeAll();
		$filesystem->dumpComposerFile();

		$process = (new ShellProcess($this->codex, $style))
			->withMessage('Processing dependencies to scope...')
			->run(
				sprintf(
					'composer install --no-interaction --no-plugins --no-scripts --prefer-dist%s --working-dir=%s',
					$this->devMode ? '' : ' --no-dev',
					$filesystem->getBuildPath(),
				),
			);

		if ($process->isSuccessful()) {
			$process = (new ShellProcess($this->codex, $style))
				->withMessage(
					sprintf(
						'Prefixing dependencies namespace with <comment>"%s"</comment>...',
						$prefix,
					),
				)
				->run(
					sprintf(
						'%s add-prefix --force --quiet --config=%s',
						$filesystem->getBinPath(),
						$filesystem->getConfigPath(),
					),
					$filesystem->getBuildPath(),
				);
		}

		if ($process->isSuccessful()) {
			$process = (new ShellProcess($this->codex, $style))
				->withSuccessMessage('Dependencies namespace has been prefixed successfully')
				->run(
					sprintf(
						'composer dump -d %s',
						$filesystem->getOutputPath(),
					),
					$this->codex->getProjectPath(),
				);
		}

		$filesystem->removeBuildPath();

		return $process->getExitCode();
	}
}
