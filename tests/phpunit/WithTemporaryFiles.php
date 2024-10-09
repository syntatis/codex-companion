<?php

declare(strict_types=1);

namespace Syntatis\Tests;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function dirname;
use function md5;

trait WithTemporaryFiles
{
	private static string $tempDir;

	private static Filesystem $filesystem;

	protected static function setUpTemporaryPath(): void
	{
		self::$tempDir = Path::normalize(dirname(__DIR__, 2) . '/tmp/phpunit-' . md5(static::class));
		self::$filesystem = new Filesystem();
		self::$filesystem->mkdir(self::$tempDir);
	}

	public static function getTemporaryPath(?string $path = null): string
	{
		if ($path) {
			return Path::normalize(self::$tempDir . $path);
		}

		return Path::canonicalize(self::$tempDir);
	}

	public static function createTemporaryFile(string $path, string $content): void
	{
		self::$filesystem->dumpFile(Path::isAbsolute($path) ? $path : self::getTemporaryPath($path), $content);
	}

	protected static function tearDownTemporaryPath(): void
	{
		self::$filesystem->remove(self::$tempDir);
	}
}
