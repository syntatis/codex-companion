<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console\ProjectInitCommand;

use SplFileInfo;
use Symfony\Component\Console\Style\StyleInterface;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Console\ProjectInitCommand\Howdy\UserInputs;
use Syntatis\Codex\Companion\Contracts\Executable;
use Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles;
use Syntatis\Codex\Companion\Projects\Howdy\ProjectFiles;
use Syntatis\Codex\Companion\Projects\Howdy\ProjectProps;
use Throwable;

use function count;
use function file_exists;

/**
 * Handles the "syntatis/howdy" project intialization when "project:init"
 * command is run.
 */
class Howdy implements Executable
{
	private Codex $codex;

	public function __construct(Codex $codex)
	{
		$this->codex = $codex;
	}

	public function execute(StyleInterface $style): int
	{
		$projectProps = new ProjectProps($this->codex);
		$pluginFile = $projectProps->getPluginFile();

		/**
		 * This command is executed on initialization of a fresh project.
		 *
		 * It assumes that the main plugin file name is unchanged otherwise we may
		 * presume that the project is already initialized. This is to prevent
		 */
		$defaultPluginFile = $this->codex->getProjectPath('/plugin-name.php');

		if ($pluginFile instanceof SplFileInfo && (string) $pluginFile !== $defaultPluginFile) {
			$style->warning('Project is already initialized.');

			return 0;
		}

		if (! file_exists($defaultPluginFile)) {
			$style->error('Unable to find the plugin main file.');

			return 1;
		}

		try {
			$userInputs = new UserInputs($projectProps->getAll());
			$userInputs->execute($style);

			$projectFiles = new ProjectFiles($this->codex->getProjectPath());
			$fileCount = count($projectFiles);

			if ($fileCount > 0) {
				$initializeFiles = new InitializeFiles(
					$userInputs->getProjectProps(),
					$userInputs->getInputs(),
				);
				$style->progressStart($fileCount);

				foreach ($projectFiles as $key => $value) {
					$initializeFiles->file($value->getFileInfo());
					$style->progressAdvance();
				}

				$style->progressFinish();
			}

			$style->success('Project initialized.');

			return 0;
		} catch (Throwable $th) {
			$style->error($th->getMessage());

			return 1;
		}
	}
}