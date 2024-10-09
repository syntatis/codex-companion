<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers;

use function is_bool;
use function preg_match;

class PHPScoperRequirement
{
	/**
	 * The output string from the composer show command.
	 * `composer bin php-scoper show -N`
	 */
	private string $output;

	private static ?bool $matched = null;

	public function __construct(string $output)
	{
		self::$matched = null;
		$this->output = $output;
	}

	public function isMet(): bool
	{
		return $this->evalOutput();
	}

	private function evalOutput(): bool
	{
		if (is_bool(self::$matched)) {
			return self::$matched;
		}

		preg_match(
			'/\s?(?P<name>humbug\/php-scoper)\s/',
			$this->output,
			$matches,
		);

		self::$matched = ($matches['name'] ?? null) === 'humbug/php-scoper';

		return self::$matched;
	}
}
