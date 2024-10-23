<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers\Versions;

use Syntatis\Codex\Companion\Concerns\HandleVersioning;
use Syntatis\Codex\Companion\Contracts\Versionable;
use Version\Version;

/**
 * Handles the version number retrieved from the "Tested up to:" field in
 * the WordPress plugin header.
 */
class WPTestedUpto implements Versionable
{
	use HandleVersioning;

	private Version $version;

	private ?string $str = null;

	public function __construct(string $version)
	{
		$this->version = self::normalizeVersion($version);
	}

	public function incrementMajor(): Versionable
	{
		$this->str = null;
		$this->version->incrementMajor();

		return $this;
	}

	public function incrementMinor(): Versionable
	{
		$this->str = null;
		$this->version->incrementMinor();

		return $this;
	}

	public function __toString(): string
	{
		$str = self::removePatchPart($this->version->toString());

		if ($this->str === null) {
			$this->str = $str;
		}

		return $str;
	}
}
