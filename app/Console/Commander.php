<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class Commander extends Application
{
	protected string $projectPath;

	public function __construct(string $projectPath)
	{
		parent::__construct('Codex', '0.1.0-alpha.0');

		$this->projectPath = $projectPath;
		$this->addCommands($this->getCommands());
	}

	/** @return array<Command> */
	private function getCommands(): array
	{
		return [
			new ProjectInitCommand($this->projectPath),
			new ScoperInitCommand($this->projectPath),
			new ScoperPurgeCommand($this->projectPath),
			new VersionsUpdateCommand($this->projectPath),
		];
	}
}
