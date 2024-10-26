<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers\Versions;

class WPPluginRequiresPHP extends WPPluginTestedUpto
{
	protected string $errorMessage = 'Invalid WordPress plugin "Requires PHP" version.';
}
