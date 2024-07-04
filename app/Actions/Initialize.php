<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin\Actions;

use Composer\Composer;
use Composer\IO\ConsoleIO;
use Syntatis\ComposerProjectPlugin\Actions\Initializers\WPStarterPlugin;
use Syntatis\ComposerProjectPlugin\Contracts\Executable;
use Syntatis\ComposerProjectPlugin\Traits\ConsoleOutput;

use function Syntatis\Utils\is_blank;

class Initialize implements Executable
{
	use ConsoleOutput;

	private Composer $composer;

	private ConsoleIO $io;

	public function __construct(
		Composer $composer,
		ConsoleIO $io
	) {
		$this->composer = $composer;
		$this->io = $io;
	}

	public function execute(): int
	{
		/** @var array{syntatis?:array{project?:array{name?:string,initialized?:int|bool}}} $extra */
		$extra = $this->composer->getPackage()->getExtra();
		$project = $extra['syntatis']['project'] ?? null;

		if (is_blank($project)) {
			return self::SUCCESS;
		}

		$projectName = $extra['syntatis']['project']['name'] ?? null;

		switch ($projectName) {
			case 'wp-starter-plugin':
				return (new WPStarterPlugin($this->io))->execute();

			default:
				return self::SUCCESS;
		}
	}
}
