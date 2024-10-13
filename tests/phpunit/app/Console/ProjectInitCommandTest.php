<?php

declare(strict_types=1);

namespace Syntatis\Tests\Console;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Syntatis\Codex\Companion\Console\ProjectInitCommand;
use Syntatis\Tests\WithTemporaryFiles;

class ProjectInitCommandTest extends TestCase
{
	use WithTemporaryFiles;

	public function setUp(): void
	{
		parent::setUp();

		$this->setUpTemporaryPath();
		$this->dumpTemporaryFile(
			'composer.json',
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
							"prefix": "PluginName\\Vendor"
						}
					}
				}
			}
			CONTENT,
		);
		$this->dumpTemporaryFile(
			'plugin-name.php',
			<<<'CONTENT'
			/**
			 * Plugin bootstrap file.
			 *
			 * This file is read by WordPress to display the plugin's information in the admin area.
			 *
			 * @wordpress-plugin
			 * Plugin Name:       Plugin Name
			 * Plugin URI:        https://example.org
			 * Description:       The plugin short description.
			 * Version:           1.0.0
			 * Requires at least: 5.8
			 * Requires PHP:      7.4
			 * Author:            Author Name
			 * Author URI:        https://example.org
			 * License:           GPL-2.0+
			 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
			 * Text Domain:       plugin-name
			 * Domain Path:       /inc/languages
			 */
			CONTENT,
		);
		$this->dumpTemporaryFile(
			'scoper.inc.php',
			<<<'CONTENT'
			<?php return ["prefix" => "PluginName\\Vendor"];
			CONTENT,
		);
	}

	public function testMissingPluginMainFile(): void
	{
		$this->filesystem->remove($this->getTemporaryPath('plugin-name.php'));

		$command = new ProjectInitCommand($this->getTemporaryPath());
		$tester = new CommandTester($command);
		$tester->execute([]);

		$this->assertStringContainsString('Unable to find the plugin main file.', $tester->getDisplay());
		$this->assertSame(1, $tester->getStatusCode());
	}

	public function testHasNonDefaultPluginMainFile(): void
	{
		$this->filesystem->rename(
			$this->getTemporaryPath('plugin-name.php'),
			$this->getTemporaryPath('awesome-plugin-name.php'),
		);

		$command = new ProjectInitCommand($this->getTemporaryPath());
		$tester = new CommandTester($command);
		$tester->execute([]);

		$this->assertStringContainsString('Project is already initialized.', $tester->getDisplay());
		$this->assertSame(0, $tester->getStatusCode());
	}

	public function testMissingScoperConfigFile(): void
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

		$command = new ProjectInitCommand($this->getTemporaryPath());
		$tester = new CommandTester($command);
		$tester->execute([]);

		$this->assertStringContainsString('[ERROR] Missing required info: php_vendor_prefix', $tester->getDisplay());
		$this->assertSame(1, $tester->getStatusCode());
	}

	/** @dataProvider dataInputPluginSlug */
	public function testInputPluginSlug(string $input, string $display): void
	{
		$command = new ProjectInitCommand($this->getTemporaryPath());
		$tester = new CommandTester($command);
		$tester->setInputs([$input]);
		$tester->execute([]);

		$this->assertStringContainsString($display, $tester->getDisplay());
		$this->assertSame(0, $tester->getStatusCode());
	}

	public static function dataInputPluginSlug(): array
	{
		return [
			['awesome plugin name', 'Plugin name [Awesome Plugin Name]:'],
			['awesome_plugin_name', 'Plugin name [Awesome Plugin Name]:'],
		];
	}

	/** @dataProvider dataInputPluginSlugInvalid */
	public function testInputPluginSlugInvalid(string $input): void
	{
		$command = new ProjectInitCommand($this->getTemporaryPath());
		$tester = new CommandTester($command);
		$tester->setInputs([$input]);
		$tester->execute([]);

		$this->assertStringContainsString('The plugin slug cannnot be blank.', $tester->getDisplay());
		$this->assertSame(1, $tester->getStatusCode());
	}

	public static function dataInputPluginSlugInvalid(): array
	{
		return [
			[''],
		];
	}
}
