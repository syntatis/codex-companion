<?php

declare(strict_types=1);

namespace Syntatis\Tests;

use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Syntatis\Utils\Val;

use function dirname;
use function is_string;
use function trim;

trait WithTemporaryFiles
{
	private static string $tempDir;

	private static Filesystem $filesystem;

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		$filesystem = new Filesystem();
		$tempDir = Path::normalize(dirname(__DIR__, 2) . '/tmp/phpunit-dumps');
		$filesystem->mkdir($tempDir);

		self::$tempDir = $tempDir;
		self::$filesystem = $filesystem;
	}

	public static function tearDownAfterClass(): void
	{
		self::$filesystem->remove(self::$tempDir);

		parent::tearDownAfterClass();
	}

	protected function tearDown(): void
	{
		$this->tearDownTemporaryPath();

		parent::tearDown();
	}

	public function getTemporaryPath(?string $path = null): string
	{
		if (Val::isBlank($path)) {
			return self::$tempDir;
		}

		if (is_string($path) && Path::isAbsolute($path)) {
			throw new InvalidArgumentException('Path must be relative');
		}

		return Path::canonicalize(self::$tempDir . '/' . trim($path, '\\/'));
	}

	public function dumpTemporaryFile(string $path, string $content): void
	{
		if (Path::isAbsolute($path)) {
			throw new InvalidArgumentException('Path must be relative');
		}

		self::$filesystem->dumpFile($this->getTemporaryPath($path), $content);
	}

	protected function tearDownTemporaryPath(): void
	{
		$finder = Finder::create()
			->in(self::$tempDir)
			->ignoreDotFiles(false)
			->sortByName();

		if (! $finder->hasResults()) {
			return;
		}

		self::$filesystem->remove($finder);
	}
}
