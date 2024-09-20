<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin\Actions\Initializers;

use Composer\IO\ConsoleIO;
use SplFileInfo;
use Symfony\Component\Console\Helper\ProgressBar;
use Syntatis\ComposerProjectPlugin\Actions\Initializers\Howdy\ProjectFiles;
use Syntatis\ComposerProjectPlugin\Actions\Initializers\Howdy\SearchReplace;
use Syntatis\ComposerProjectPlugin\Actions\Initializers\Howdy\UserInputs;
use Syntatis\ComposerProjectPlugin\Contracts\Executable;
use Syntatis\ComposerProjectPlugin\Traits\Common;
use Syntatis\ComposerProjectPlugin\Traits\ConsoleOutput;
use Throwable;

use function count;
use function file_exists;
use function sprintf;
use function str_replace;

class Howdy implements Executable
{
	use Common;
	use ConsoleOutput;

	public function __construct(ConsoleIO $io)
	{
		$this->io = $io;
		$this->consoleOutputPrefix = '[howdy]';
	}

	public function execute(): int
	{
		/** @todo Add the ability to allow developers to reinitialize existing the project. */
		if (! file_exists(self::getRootDir() . '/plugin-name.php')) {
			$this->io->write($this->info('Project is already initialized.'));

			return self::SUCCESS;
		}

		$confirm = $this->io->askConfirmation(
			$this->prefixed('Would you like to customize your WordPress plugin project [' . $this->asComment('yes') . ']? '),
		);

		if (! $confirm) {
			$this->io->write($this->comment('Skip project customization.'));

			return self::SUCCESS;
		}

		try {
			$userInputs = new UserInputs($this->io, $this->consoleOutputPrefix);
			$composerFile = new SplFileInfo(self::getComposerFile());
			$projectFiles = new ProjectFiles($composerFile);
			$projectRoot = $projectFiles->getRootDirectory();
			$fileCount = count($projectFiles);

			if ($fileCount > 0) {
				ProgressBar::setFormatDefinition(
					'file-search-replace',
					$this->prefixed('%current%/%max% [%bar%] %comment%'),
				);
				$progressBar = $this->io->getProgressBar($fileCount);
				$progressBar->setFormat('file-search-replace');
				$searchReplace = new SearchReplace($userInputs);

				foreach ($projectFiles as $key => $value) {
					$message = sprintf('(%s)', str_replace($projectRoot, '', $value->getRealPath()));
					$progressBar->setMessage($message, 'comment');
					$searchReplace->file($value->getFileInfo());
					$progressBar->advance();
				}

				$progressBar->setMessage("<info>Done!</info>\n", 'comment');
				$progressBar->finish();
			}
		} catch (Throwable $th) {
			$this->io->error($th->getMessage());
			$this->io->write($this->comment('Project customization is cancelled.'));
		}

		return self::SUCCESS;
	}
}
