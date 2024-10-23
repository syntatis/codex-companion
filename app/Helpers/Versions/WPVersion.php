<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers\Versions;

use Syntatis\Codex\Companion\Concerns\HandleVersioning;
use Syntatis\Codex\Companion\Contracts\Versionable;
use Syntatis\Codex\Companion\Contracts\VersionPatchIncrementable;
use Version\Version;

/**
 * Handle WordPress "Stable Tag" version.
 */
class WPVersion implements Versionable, VersionPatchIncrementable
{
	use HandleVersioning;

	private Version $version;

	public function __construct(string $version)
	{
		$this->version = self::normalizeVersion($version);
	}

	public function incrementMajor(): Versionable
	{
		$this->version = $this->version->incrementMajor();

		return $this;
	}

	public function incrementMinor(): Versionable
	{
		$this->version = $this->version->incrementMinor();

		return $this;
	}

	public function incrementPatch(): Versionable
	{
		$this->version = $this->version->incrementPatch();

		return $this;
	}

	public function __toString(): string
	{
		return $this->version->toString();
	}
}
