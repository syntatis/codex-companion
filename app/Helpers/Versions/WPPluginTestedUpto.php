<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers\Versions;

use Syntatis\Codex\Companion\Concerns\HandleVersioning;
use Syntatis\Codex\Companion\Contracts\Versionable;
use Syntatis\Codex\Companion\Exceptions\InvalidVersion;
use Version\Version;

use function sprintf;

/**
 * This class handles the version number retrieved from the "Tested up to"
 * field in the WordPress plugin header.
 */
class WPPluginTestedUpto implements Versionable
{
	use HandleVersioning;

	protected Version $version;

	protected string $field = 'Tested up to';

	public function __construct(string $version)
	{
		$normalizedVersion = self::normalizeVersion($version);

		if ($normalizedVersion === false) {
			throw new InvalidVersion(sprintf('Invalid "%s" version: %s', $this->field, $version));
		}

		$this->version = $normalizedVersion;
	}

	public function incrementMajor(): Versionable
	{
		$this->version->incrementMajor();

		return $this;
	}

	public function incrementMinor(): Versionable
	{
		$this->version->incrementMinor();

		return $this;
	}

	public function __toString(): string
	{
		/**
		 * As described in the WordPress handbook, a plugin should not break with
		 * a minor update. As such, WordPress will ignore the patch version of
		 * the "Tested up to" field.
		 */
		return self::removePatchPart($this->version->toString());
	}
}
