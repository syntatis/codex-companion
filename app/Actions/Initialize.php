<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Actions;

use Composer\Composer;
use Composer\IO\ConsoleIO;
use Syntatis\Codex\Companion\Actions\Initializers\Howdy;
use Syntatis\Codex\Companion\Contracts\Executable;
use Syntatis\Codex\Companion\Traits\ConsoleOutput;
use Syntatis\Utils\Val;

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
		/** @var array{codex?:array{project?:array{name?:string,initialized?:int|bool}}} $extra */
		$extra = $this->composer->getPackage()->getExtra();
		$project = $extra['codex']['project'] ?? null;

		if (Val::isBlank($project)) {
			return self::SUCCESS;
		}

		$projectName = $extra['codex']['project']['name'] ?? null;

		switch ($projectName) {
			case 'howdy':
				return (new Howdy($this->io))->execute();

			default:
				return self::SUCCESS;
		}
	}
}
