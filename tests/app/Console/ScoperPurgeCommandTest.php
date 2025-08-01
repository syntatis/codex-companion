<?php

declare(strict_types=1);

namespace Syntatis\Tests\Console;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Console\Commander;
use Syntatis\Codex\Companion\Helpers\PHPScoperFilesystem;
use Syntatis\Tests\WithTemporaryFiles;

class ScoperPurgeCommandTest extends TestCase
{
	use WithTemporaryFiles;

	public function setUp(): void
	{
		parent::setUp();

		$this->dumpTemporaryFile(
			'composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": "app/"
					}
				}
			}
			CONTENT,
		);
	}

	public function testPurgeConfirmMessage(): void
	{
		$command = new Commander($this->getTemporaryPath());
		$tester = new CommandTester($command->get('scoper:purge'));
		$tester->execute([]);

		$this->assertStringContainsString('This command will delete all scoped dependencies.', $tester->getDisplay());
		$this->assertStringContainsString('Do you want to proceed? (yes/no) [no]:', $tester->getDisplay());
	}

	public function testPurgeFilesNoAnswer(): void
	{
		$filesystem = new PHPScoperFilesystem(new Codex($this->getTemporaryPath()));

		// Main files.
		$this->dumpTemporaryFile('composer.json', '{}');
		$this->dumpTemporaryFile('plugin-name.php', '<?php');

		// Build files.
		self::$filesystem->dumpFile($filesystem->getBuildPath('composer.json'), '{}');
		self::$filesystem->dumpFile($filesystem->getBuildPath('plugin-name.php'), '<?php');

		// Output files.
		self::$filesystem->dumpFile($filesystem->getOutputPath('composer.json'), '{}');
		self::$filesystem->dumpFile($filesystem->getOutputPath('plugin-name.php'), '<?php');

		$this->assertFileExists($filesystem->getBuildPath('composer.json'));
		$this->assertFileExists($filesystem->getBuildPath('plugin-name.php'));
		$this->assertFileExists($filesystem->getOutputPath('composer.json'));
		$this->assertFileExists($filesystem->getOutputPath('plugin-name.php'));

		$command = new Commander($this->getTemporaryPath());
		$tester = new CommandTester($command->get('scoper:purge'));
		$tester->setInputs(['no']);
		$tester->execute([]);

		$this->assertFileExists($filesystem->getBuildPath('composer.json'));
		$this->assertFileExists($filesystem->getBuildPath('plugin-name.php'));
		$this->assertFileExists($filesystem->getOutputPath('composer.json'));
		$this->assertFileExists($filesystem->getOutputPath('plugin-name.php'));

		// Main files should not be deleted.
		$this->assertFileExists($this->getTemporaryPath('composer.json'));
		$this->assertFileExists($this->getTemporaryPath('plugin-name.php'));
	}

	public function testPurgeFilesYesAnswer(): void
	{
		$filesystem = new PHPScoperFilesystem(new Codex($this->getTemporaryPath()));

		// Main files.
		$this->dumpTemporaryFile('composer.json', '{}');
		$this->dumpTemporaryFile('plugin-name.php', '<?php');

		// Build files.
		self::$filesystem->dumpFile($filesystem->getBuildPath('composer.json'), '{}');
		self::$filesystem->dumpFile($filesystem->getBuildPath('plugin-name.php'), '<?php');

		// Output files.
		self::$filesystem->dumpFile($filesystem->getOutputPath('composer.json'), '{}');
		self::$filesystem->dumpFile($filesystem->getOutputPath('plugin-name.php'), '<?php');

		$this->assertFileExists($filesystem->getBuildPath('composer.json'));
		$this->assertFileExists($filesystem->getBuildPath('plugin-name.php'));
		$this->assertFileExists($filesystem->getOutputPath('composer.json'));
		$this->assertFileExists($filesystem->getOutputPath('plugin-name.php'));

		$command = new Commander($this->getTemporaryPath());
		$tester = new CommandTester($command->get('scoper:purge'));
		$tester->setInputs(['yes']);
		$tester->execute([]);

		$this->assertFileDoesNotExist($filesystem->getBuildPath('composer.json'));
		$this->assertFileDoesNotExist($filesystem->getBuildPath('plugin-name.php'));
		$this->assertFileDoesNotExist($filesystem->getOutputPath('composer.json'));
		$this->assertFileDoesNotExist($filesystem->getOutputPath('plugin-name.php'));

		// Main files should not be deleted.
		$this->assertFileExists($this->getTemporaryPath('composer.json'));
		$this->assertFileExists($this->getTemporaryPath('plugin-name.php'));
	}
}
