<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console;

use Symfony\Component\Console\Command\Command;
use Syntatis\Codex\Companion\Codex;

abstract class BaseCommand extends Command
{
	protected Codex $codex;

	public function __construct(Codex $codex)
	{
		parent::__construct();

		$this->codex = $codex;
	}
}
