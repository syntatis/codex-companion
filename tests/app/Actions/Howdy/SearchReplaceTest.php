<?php

declare(strict_types=1);

namespace Syntatis\Tests\Actions\Howdy;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Syntatis\Codex\Companion\Actions\Initializers\Howdy\SearchReplace;
use Syntatis\Codex\Companion\Actions\Initializers\Howdy\UserInputs;
use Syntatis\Tests\Traits\WithTempFiles;

use function file_get_contents;

class SearchReplaceTest extends TestCase
{
	use WithTempFiles;

	public function setUp(): void
	{
		parent::setUp();

		self::setUpTempDir();
	}

	public function tearDown(): void
	{
		self::tearDownTempDir();

		parent::tearDown();
	}

	/** @dataProvider dataExecuteCommonFiles */
	public function testExecuteCommonFiles(string $fileName, string $content, string $expect): void
	{
		$file = self::getTempDir('/' . $fileName);

		self::$filesystem->dumpFile($file, $content);

		/** @var MockObject&UserInputs $mock */
		$mock = $this->getMockBuilder(UserInputs::class)
			->disableOriginalConstructor()
			->getMock();
		$mock
			->expects(self::exactly(2))
			->method('get')
			->will(self::returnCallback(static function ($param) {
				switch ($param) {
					case 'php_namespace':
						return 'Acme\AwesomePlugin';

					case 'vendor_prefix':
						return 'AAV\Lib';

					case 'wp_plugin_name':
						return 'Acme Awesome Plugin';

					case 'wp_plugin_slug':
						return 'acme-awesome-plugin';

					default:
						return [
							'vendor_prefix' => 'AAV\Lib',
							'php_namespace' => 'Acme\AwesomePlugin',
							'wp_plugin_name' => 'Acme Awesome Plugin',
							'wp_plugin_slug' => 'acme-awesome-plugin',
						];
				}
			}));

		$fileInfo = new SplFileInfo($file);
		$searchReplace = new SearchReplace($mock);
		$searchReplace->file($fileInfo);

		self::assertEquals($expect, file_get_contents($file));
	}

	public static function dataExecuteCommonFiles(): iterable
	{
		yield [
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
					"syntatis": {
						"project": {
							"name": "howdy"
						}
					}
				}
			}
			CONTENT,
			<<<'CONTENT'
			{
			    "name": "syntatis/howdy",
			    "autoload": {
			        "psr-4": {
			            "Acme\\AwesomePlugin\\": "app/"
			        }
			    },
			    "extra": {
			        "syntatis": {
			            "project": {
			                "name": "howdy"
			            }
			        }
			    }
			}
			CONTENT,
		];

		yield [
			'package.json',
			<<<'CONTENT'
			{
			    "name": "@syntatis/howdy",
			    "version": "1.0.0",
				"description": "A starting point for your next plugin project",
				"files": [
					"app",
					"dist-autoload",
					"dist",
					"inc",
					"index.php",
					"uninstall.php",
					"plugin-name.php"
				]
			}
			CONTENT,
			<<<'CONTENT'
			{
			    "name": "@syntatis/howdy",
			    "version": "1.0.0",
			    "description": "A starting point for your next plugin project",
			    "files": [
			        "app",
			        "dist-autoload",
			        "dist",
			        "inc",
			        "index.php",
			        "uninstall.php",
			        "acme-awesome-plugin.php"
			    ]
			}
			CONTENT,
		];

		yield 'scoper.inc.php' => [
			'scoper.inc.php',
			<<<'CONTENT'
			<?php
			declare(strict_types=1);

			use Isolated\Symfony\Component\Finder\Finder;

			return [
				'prefix' => 'PluginName\\Vendor',
				'exclude-namespaces' => ['PluginName'],
			];
			CONTENT,
			<<<'CONTENT'
			<?php
			declare(strict_types=1);

			use Isolated\Symfony\Component\Finder\Finder;

			return [
				'prefix' => 'AAV\\Lib',
				'exclude-namespaces' => ['Acme\\AwesomePlugin'],
			];
			CONTENT,
		];

		yield 'php_namespace' => [
			'foo.php',
			<<<'CONTENT'
			<?php
			declare(strict_types=1);

			namespace PluginName;

			use RecursiveDirectoryIterator;
			use PluginName\Vendor\Syntatis\WPHook\Contract\WithHook;
			use PluginName\Vendor\Syntatis\WPHook\Hook;
			use PluginName\HelloWorld\Plugin;
			CONTENT,
			<<<'CONTENT'
			<?php
			declare(strict_types=1);

			namespace Acme\AwesomePlugin;

			use RecursiveDirectoryIterator;
			use AAV\Lib\Syntatis\WPHook\Contract\WithHook;
			use AAV\Lib\Syntatis\WPHook\Hook;
			use Acme\AwesomePlugin\HelloWorld\Plugin;
			CONTENT,
		];

		yield [
			'foo.php',
			<<<'CONTENT'
			<?php
			window.__pluginName = "bar";
			CONTENT,
			<<<'CONTENT'
			<?php
			window.__acmeAwesomePlugin = "bar";
			CONTENT,
		];
	}

	/**
	 * @dataProvider dataExecuteMainFile
	 * @group file-renamed
	 */
	public function testExecuteMainFile(string $content, string $expect): void
	{
		// Original file path.
		$filePath = self::getTempDir('/plugin-name.php');

		self::$filesystem->dumpFile($filePath, $content);

		/** @var MockObject&UserInputs $mock */
		$mock = $this->getMockBuilder(UserInputs::class)
			->disableOriginalConstructor()
			->getMock();
		$mock
			->expects(self::exactly(2))
			->method('get')
			->will(self::returnCallback(static function ($param) {
				switch ($param) {
					case 'php_namespace':
						return 'Acme\AwesomePlugin';

					case 'vendor_prefix':
						return 'AAV\Lib';

					case 'wp_plugin_name':
						return 'Acme Awesome Plugin';

					case 'wp_plugin_slug':
						return 'acme-awesome-plugin';

					default:
						return [
							'php_namespace' => 'Acme\AwesomePlugin',
							'vendor_prefix' => 'AAV\Lib',
							'wp_plugin_name' => 'Acme Awesome Plugin',
							'wp_plugin_slug' => 'acme-awesome-plugin',
						];
				}
			}));

		$fileInfo = new SplFileInfo($filePath);
		$searchReplace = new SearchReplace($mock);
		$searchReplace->file($fileInfo);

		// Renamed file path.
		$newFile = self::getTempDir('/acme-awesome-plugin.php');

		self::assertFileExists($newFile);
		self::assertEquals($expect, file_get_contents($newFile));
	}

	public static function dataExecuteMainFile(): iterable
	{
		yield [
			<<<'CONTENT'
			/**
			 * Plugin bootstrap file.
			 *
			 * This file is read by WordPress to display the plugin's information in the admin area.
			 *
			 * @wordpress-plugin
			 * Plugin Name:       Plugin Name
			 * Plugin URI:        https://example.org
			 * Description:       This is a description of the plugin.
			 * Version:           1.0.0
			 * Requires at least: 5.8
			 * Requires PHP:      7.4
			 * Author:            John Doe
			 * Author URI:        https://example.org
			 * License:           GPL-2.0+
			 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
			 * Text Domain:       plugin-name
			 * Domain Path:       /inc/languages
			 */
			CONTENT,
			<<<'CONTENT'
			/**
			 * Plugin bootstrap file.
			 *
			 * This file is read by WordPress to display the plugin's information in the admin area.
			 *
			 * @wordpress-plugin
			 * Plugin Name:       Acme Awesome Plugin
			 * Plugin URI:        https://example.org
			 * Description:       This is a description of the plugin.
			 * Version:           1.0.0
			 * Requires at least: 5.8
			 * Requires PHP:      7.4
			 * Author:            John Doe
			 * Author URI:        https://example.org
			 * License:           GPL-2.0+
			 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
			 * Text Domain:       acme-awesome-plugin
			 * Domain Path:       /inc/languages
			 */
			CONTENT,
		];
	}

	/**
	 * @dataProvider dataExecutePotFile
	 * @group file-renamed
	 */
	public function testExecutPotFile(string $content, string $expect): void
	{
		// Original file path.
		$filePath = self::getTempDir('/plugin-name.pot');

		self::$filesystem->dumpFile($filePath, $content);

		/** @var MockObject&UserInputs $mock */
		$mock = $this->getMockBuilder(UserInputs::class)
			->disableOriginalConstructor()
			->getMock();
		$mock
			->expects(self::exactly(2))
			->method('get')
			->will(self::returnCallback(static function ($param) {
				switch ($param) {
					case 'php_namespace':
						return 'Acme\AwesomePlugin';

					case 'vendor_prefix':
						return 'AAV\Lib';

					case 'wp_plugin_name':
						return 'Acme Awesome Plugin';

					case 'wp_plugin_slug':
						return 'acme-awesome-plugin';

					default:
						return [
							'php_namespace' => 'Acme\AwesomePlugin',
							'vendor_prefix' => 'AAV\Lib',
							'wp_plugin_name' => 'Acme Awesome Plugin',
							'wp_plugin_slug' => 'acme-awesome-plugin',
						];
				}
			}));

		$fileInfo = new SplFileInfo($filePath);
		$searchReplace = new SearchReplace($mock);
		$searchReplace->file($fileInfo);

		// Renamed file path.
		$newFile = self::getTempDir('/acme-awesome-plugin.pot');

		self::assertFileExists($newFile);
		self::assertEquals($expect, file_get_contents($newFile));
	}

	public static function dataExecutePotFile(): iterable
	{
		yield [
			<<<'CONTENT'
			"Project-Id-Version: Foo Bar 1.0.0\n"
			"Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/plugin-name\n"
			"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
			"Language-Team: LANGUAGE <LL@li.org>\n"
			"MIME-Version: 1.0\n"
			"Content-Type: text/plain; charset=UTF-8\n"
			"Content-Transfer-Encoding: 8bit\n"
			"POT-Creation-Date: 2024-07-21T11:06:28+00:00\n"
			"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
			"X-Generator: WP-CLI 2.10.0\n"
			"X-Domain: plugin-name\n"

			#. Plugin Name of the plugin
			#: plugin-name.php
			msgid "Plugin Name"
			msgstr ""

			#. Plugin URI of the plugin
			#: plugin-name.php
			msgid "https://example.org/plugin/plugin-name"
			msgstr ""
			CONTENT,
			<<<'CONTENT'
			"Project-Id-Version: Foo Bar 1.0.0\n"
			"Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/acme-awesome-plugin\n"
			"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
			"Language-Team: LANGUAGE <LL@li.org>\n"
			"MIME-Version: 1.0\n"
			"Content-Type: text/plain; charset=UTF-8\n"
			"Content-Transfer-Encoding: 8bit\n"
			"POT-Creation-Date: 2024-07-21T11:06:28+00:00\n"
			"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
			"X-Generator: WP-CLI 2.10.0\n"
			"X-Domain: acme-awesome-plugin\n"

			#. Plugin Name of the plugin
			#: acme-awesome-plugin.php
			msgid "Acme Awesome Plugin"
			msgstr ""

			#. Plugin URI of the plugin
			#: acme-awesome-plugin.php
			msgid "https://example.org/plugin/acme-awesome-plugin"
			msgstr ""
			CONTENT,
		];
	}
}
