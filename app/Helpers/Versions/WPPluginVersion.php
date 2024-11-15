<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers\Versions;

use Syntatis\Codex\Companion\Concerns\HandleVersioning;
use Syntatis\Codex\Companion\Contracts\Versionable;
use Syntatis\Codex\Companion\Contracts\VersionPatchIncrementable;
use Syntatis\Codex\Companion\Exceptions\InvalidVersion;
use Version\Version;

/**
 * Handle WordPress "Stable Tag" version.
 */
class WPPluginVersion implements Versionable, VersionPatchIncrementable
{
	use HandleVersioning;

	private Version $version;

	public function __construct(string $version)
	{
		$normalizedVersion = self::normalizeVersion($version);

		if ($normalizedVersion === false) {
			throw new InvalidVersion('Invalid "Stable tag" version: ' . $version);
		}

		$this->version = $normalizedVersion;
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
