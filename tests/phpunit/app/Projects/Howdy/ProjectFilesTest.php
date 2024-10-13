<?php

declare(strict_types=1);

namespace Syntatis\Tests\Projects\Howdy;

use PHPUnit\Framework\TestCase;
use Syntatis\Codex\Companion\Projects\Howdy\ProjectFiles;
use Syntatis\Tests\WithTemporaryFiles;

use function count;

class ProjectFilesTest extends TestCase
{
	use WithTemporaryFiles;

	public function testIteratedFiles(): void
	{
		$this->dumpTemporaryFiles([
			// Should be included
			'composer.json',
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
			'dist-autoload/autoload.php',
			'dist/index.js',
		]);

		$files = new ProjectFiles($this->getTemporaryPath());

		$this->assertSame(6, count($files));

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
	}

	private function dumpTemporaryFiles(array $files): void
	{
		foreach ($files as $file) {
			$this->dumpTemporaryFile($file, '');
		}
	}
}
