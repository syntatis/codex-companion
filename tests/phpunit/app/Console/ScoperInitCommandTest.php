<?php

declare(strict_types=1);

namespace Syntatis\Tests\Console;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Syntatis\Codex\Companion\Console\ScoperInitCommand;
use Syntatis\Tests\WithTemporaryFiles;

class ScoperInitCommandTest extends TestCase
{
	use WithTemporaryFiles;

	public function setUp(): void
	{
		parent::setUp();

		self::setUpTemporaryPath();
		self::createTemporaryFile(
			'/composer.json',
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

	public function tearDown(): void
	{
		self::tearDownTemporaryPath();

		parent::tearDown();
	}

	public function testConfirm(): void
	{
		$command = new ScoperInitCommand(self::getTemporaryPath());
		$tester = new CommandTester($command);
		$tester->execute([]);

		$this->assertStringContainsString('This command will prefix the dependencies namespace', $tester->getDisplay());
		$this->assertStringContainsString('Do you want to proceed? (yes/no) [yes]:', $tester->getDisplay());
	}

	public function testConfirmNo(): void
	{
		$command = new ScoperInitCommand(self::getTemporaryPath());
		$tester = new CommandTester($command);
		$tester->setInputs(['no']);
		$tester->execute([]);

		$this->assertSame(0, $tester->getStatusCode());
		$this->assertStringContainsString('The command has been aborted', $tester->getDisplay());
	}
}
