<?php

declare(strict_types=1);

namespace Syntatis\Tests\Traits;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function dirname;
use function md5;

trait WithTempFiles
{
	private static string $tempDir;

	protected static Filesystem $filesystem;

	protected static function setUpTempDir(): void
	{
		self::$tempDir = Path::normalize(dirname(__DIR__, 3) . '/tmp/phpunit/' . md5(static::class));
		self::$filesystem = new Filesystem();
		self::$filesystem->mkdir(self::$tempDir);
	}

	public static function getTempDir(string $path): string
	{
		return self::$tempDir . $path;
	}

	protected static function tearDownTempDir(): void
	{
		self::$filesystem->remove(self::$tempDir);
	}
}
