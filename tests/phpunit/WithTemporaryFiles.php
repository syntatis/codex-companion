<?php

declare(strict_types=1);

namespace Syntatis\Tests;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function dirname;
use function md5;

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
		if ($path) {
			return Path::normalize($this->tempDir . $path);
		}

		return Path::normalize($this->tempDir);
	}

	public function dumpTemporaryFile(string $path, string $content): void
	{
		$this->filesystem->dumpFile($this->getTemporaryPath($path), $content);
	}

	protected function tearDownTemporaryPath(): void
	{
		$this->filesystem->remove($this->tempDir);
	}
}
