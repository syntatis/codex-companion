<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Concerns;

use Syntatis\Utils\Val;
use Version\Exception\InvalidVersionString;
use Version\Extension\Build;
use Version\Extension\PreRelease;
use Version\Version;

use function is_string;
use function ltrim;
use function preg_match;
use function preg_replace;

trait HandleVersioning
{
	/**
	 * Normalize version before it's validated.
	 */
	protected static function normalizeVersion(string $version, ?string $errorMessage = null): Version
	{
		self::isVersion(ltrim($version, 'v'), $matches);

		$major = $matches['major'] ?? null;
		$minor = $matches['minor'] ?? null;

		if (Val::isBlank($major) || Val::isBlank($minor)) {
			throw InvalidVersionString::notParsable(
				$errorMessage ?? 'Invalid version string: ' . $version,
			);
		}

		$patch = $matches['patch'] ?? 0;
		$prerelease = $matches['prerelease'] ?? null;
		$buildmetadata = $matches['buildmetadata'] ?? null;

		$version = Version::from(
			(int) $major,
			(int) $minor,
			(int) $patch,
			is_string($prerelease) ? PreRelease::fromString($prerelease) : null,
			is_string($buildmetadata) ? Build::fromString($buildmetadata) : null,
		);

		return $version;
	}

	/** @param array<mixed>|null $matches */
	private static function isVersion(string $version, ?array &$matches = null): bool
	{
		/**
		 * Match version without the patch, pre-release, and build metadata parts
		 * since WordPress only accepts stable version.
		 *
		 * @see https://semver.org/#is-there-a-suggested-regular-expression-regex-to-check-a-semver-string
		 */
		$matched = preg_match('/^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)(?:\.(?P<patch>0|[1-9]\d*))?$/', $version, $matches);

		return $matched === 1;
	}

	/**
	 * Some version in WordPress does not require the patch part.
	 */
	private static function removePatchPart(string $version): string
	{
		return (string) preg_replace('/\.\d+$/', '', $version);
	}
}
