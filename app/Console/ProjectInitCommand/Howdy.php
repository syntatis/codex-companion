<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console\ProjectInitCommand;

use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Filesystem\Path;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Console\ProjectInitCommand\Howdy\UserInputPrompts;
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

		/**
		 * Normalize the plugin file path to handle the descrepencies in the path
		 * format between different operating systems.
		 */
		$pluginFile = Path::normalize((string) $projectProps->getPluginFile());

		if (! file_exists($pluginFile)) {
			$style->error('Unable to find the plugin main file.');

			return 1;
		}

		/**
		 * This command is executed on initialization of a fresh project.
		 *
		 * It assumes that the main plugin file name is unchanged. If the file is
		 * found to be different from the default, we are going to assume that
		 * the project is already initialized.
		 *
		 * It is too risky to proceed, if the file is already changed as we could
		 * not fully determine, what are the changes made to the file.
		 */
		if ($pluginFile !== $this->codex->getProjectPath('/plugin-name.php')) {
			$style->warning('Project is already initialized.');

			return 0;
		}

		try {
			$userInputs = new UserInputPrompts($projectProps->get(), $style);
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
