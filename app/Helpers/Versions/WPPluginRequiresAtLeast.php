<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers\Versions;

class WPPluginRequiresAtLeast extends WPPluginTestedUpto
{
	protected string $errorMessage = 'Invalid WordPress plugin "Requires at least" version.';
}
