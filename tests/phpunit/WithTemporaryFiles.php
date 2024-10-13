<?php

declare(strict_types=1);

namespace Syntatis\Tests;

use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Syntatis\Utils\Val;

use function dirname;
use function is_string;
use function md5;
use function trim;

trait WithTemporaryFiles
{
	private string $tempDir;

	private Filesystem $filesystem;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpTemporaryPath();
	}

	protected function tearDown(): void
	{
		$this->tearDownTemporaryPath();

		parent::tearDown();
	}

	protected function setUpTemporaryPath(): void
	{
		$this->tempDir = Path::normalize(dirname(__DIR__, 2) . '/tmp/phpunit-' . md5(static::class));
		$this->filesystem = new Filesystem();
		$this->filesystem->mkdir($this->tempDir);
	}

	public function getTemporaryPath(?string $path = null): string
	{
		if (Val::isBlank($path)) {
			return $this->tempDir;
		}

		if (is_string($path) && Path::isAbsolute($path)) {
			throw new InvalidArgumentException('Path must be relative');
		}

		$path = trim($path, '\\/.');

		return Path::normalize($this->tempDir . '/' . $path);
	}

	public function dumpTemporaryFile(string $path, string $content): void
	{
		if (Path::isAbsolute($path)) {
			throw new InvalidArgumentException('Path must be relative');
		}

		$this->filesystem->dumpFile($this->getTemporaryPath($path), $content);
	}

	protected function tearDownTemporaryPath(): void
	{
		$this->filesystem->remove($this->tempDir);
	}
}
