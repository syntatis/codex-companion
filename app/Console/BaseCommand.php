<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console;

use Symfony\Component\Console\Command\Command;

abstract class BaseCommand extends Command
{
	protected string $projectPath;

	public function __construct(string $projectPath)
	{
		parent::__construct();

		$this->projectPath = $projectPath;
	}
}
