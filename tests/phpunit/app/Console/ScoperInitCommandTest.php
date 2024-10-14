<?php

declare(strict_types=1);

namespace Syntatis\Tests\Console;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Syntatis\Codex\Companion\Console\Commander;
use Syntatis\Tests\WithTemporaryFiles;

class ScoperInitCommandTest extends TestCase
{
	use WithTemporaryFiles;

	/** @dataProvider dataConfirmMessage */
	public function testConfirmMessage(string $composer, array $inputs, string $message): void
	{
		$this->dumpTemporaryFile('composer.json', $composer);

		$command = new Commander($this->getTemporaryPath());
		$tester = new CommandTester($command->get('scoper:init'));
		$tester->execute($inputs);

		$this->assertStringContainsString($message, $tester->getDisplay());
		$this->assertStringContainsString('Do you want to proceed? (yes/no) [yes]:', $tester->getDisplay());
	}

	public static function dataConfirmMessage(): iterable
	{
		yield [
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
			[], // Inputs.
			'This command will prefix the dependencies namespace',
		];

		yield [
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
			['--no-dev' => true ], // Inputs.
			'The packages listed in "install-dev" will be skipped.',
		];

		yield [
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": "app/"
					}
				},
				"extra": {
					"codex": {
						"scoper": {
							"prefix": "FOO\\"
						}
					}
				}
			}
			CONTENT,
			[], // Inputs.
			'This command will prefix the dependencies namespace with "FOO".',
		];

		yield [
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": "app/"
					}
				},
				"extra": {
					"codex": {
						"scoper": {
							"prefix": "FOO\\"
						}
					}
				}
			}
			CONTENT,
			['--no-dev' => true], // Inputs.
			'The packages listed in "install-dev" will be skipped.',
		];
	}

	public function testConfirmNo(): void
	{
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

		$command = new Commander($this->getTemporaryPath());
		$tester = new CommandTester($command->get('scoper:init'));
		$tester->setInputs(['no']);
		$tester->execute([]);

		$this->assertSame(0, $tester->getStatusCode());
		$this->assertStringContainsString('The command has been aborted', $tester->getDisplay());
	}
}
