<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers;

use Syntatis\Codex\Companion\Codex;

class WPPluginVersioningFilesystem
{
	private Codex $codex;

	public function __construct(Codex $codex)
	{
		$this->codex = $codex;
	}
}
