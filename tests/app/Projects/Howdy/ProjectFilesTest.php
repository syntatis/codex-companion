<?php

declare(strict_types=1);

namespace Syntatis\Tests\Projects\Howdy;

use PHPUnit\Framework\TestCase;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Projects\Howdy\ProjectFiles;
use Syntatis\Tests\WithTemporaryFiles;

use function count;
use function json_encode;

class ProjectFilesTest extends TestCase
{
	use WithTemporaryFiles;

	public function testIgnoredFiles(): void
	{
		$this->dumpTemporaryFile(
			// Should be included.
			'composer.json',
			json_encode(['name' => 'syntatis/howdy']),
		);
		$this->dumpTemporaryFiles([
			// Should be included.
			'src/index.js',
			'foo.php',
			'bar/hello-world.php',
			'package.json',
			'phpcs.xml.dist',

			// Should be ignored
			'.editorconfig',
			'.eslintrc.json',
			'.gitignore',
			'LICENSE',
			'composer.lock',
			'node_modules/react/main.js',
			'package-lock.json',
			'vendor/autoload.php',

			// Default Scoper output dir should be ignored.
			'dist/autoload/autoload.php',
			'dist/autoload/index.js',
		]);

		$files = new ProjectFiles(new Codex($this->getTemporaryPath()));

		foreach ($files as $file) {
			$this->assertNotContains(
				$file->getRealPath(),
				[
					$this->getTemporaryPath('.editorconfig'),
					$this->getTemporaryPath('.eslintrc.json'),
					$this->getTemporaryPath('.gitignore'),
					$this->getTemporaryPath('LICENSE'),
					$this->getTemporaryPath('composer.lock'),
					$this->getTemporaryPath('dist-autoload/autoload.php'),
					$this->getTemporaryPath('dist/index.js'),
					$this->getTemporaryPath('node_modules/react/main.js'),
					$this->getTemporaryPath('package-lock.json'),
					$this->getTemporaryPath('vendor/autoload.php'),
				],
			);
		}

		$this->assertSame(6, count($files));
	}

	public function testIgnoredFilesWithCustomScoperOutputDir(): void
	{
		$this->dumpTemporaryFile(
			// Should be included.
			'composer.json',
			json_encode([
				'name' => 'syntatis/howdy',
				'extra' => ['codex' => ['scoper' => ['output-dir' => 'foo-dist']]],
			]),
		);
		$this->dumpTemporaryFiles([
			// This is no longer ignored, since the custom scoper output dir is set.
			'dist/autoload/autoload.php',
			'dist/autoload/index.js',

			// Custom scoper output dir should be ignored.
			'foo-dist/autoload.php',
			'foo-dist/index.js',
		]);

		$files = new ProjectFiles(new Codex($this->getTemporaryPath()));

		$this->assertSame(3, count($files));

		foreach ($files as $file) {
			$this->assertNotContains(
				$file->getRealPath(),
				[
					$this->getTemporaryPath('foo-dist/autoload.php'),
					$this->getTemporaryPath('foo-dist/index.js'),
				],
			);
		}
	}

	private function dumpTemporaryFiles(array $files): void
	{
		foreach ($files as $file) {
			$this->dumpTemporaryFile($file, '');
		}
	}
}
