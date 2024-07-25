<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin\Tests\Actions\WPStarterPlugin;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Syntatis\ComposerProjectPlugin\Actions\Initializers\WPStarterPlugin\SearchReplace;
use Syntatis\ComposerProjectPlugin\Actions\Initializers\WPStarterPlugin\UserInputs;
use Syntatis\ComposerProjectPlugin\Tests\Traits\WithTempFiles;

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

	/** @dataProvider dataExecute */
	public function testExecute(string $fileName, string $content, string $expect): void
	{
		self::$filesystem->dumpFile(self::$tempDir . '/' . $fileName, $content);

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

					case 'project_name':
						return 'acme/awesome-plugin';

					case 'wp_plugin_name':
						return 'Acme Awesome Plugin';

					case 'wp_plugin_slug':
						return 'acme-awesome-plugin';

					default:
						return [
							'vendor_prefix' => 'AAV\Lib',
							'php_namespace' => 'Acme\AwesomePlugin',
							'project_name' => 'acme/awesome-plugin',
							'wp_plugin_name' => 'Acme Awesome Plugin',
							'wp_plugin_slug' => 'acme-awesome-plugin',
						];
				}
			}));

		$file = self::$tempDir . '/' . $fileName;
		$fileInfo = new SplFileInfo($file);
		$searchReplace = new SearchReplace($mock);
		$searchReplace->file($fileInfo);

		self::assertEquals($expect, file_get_contents($file));
	}

	public function testMainFile(): void
	{
		$filePath = self::$tempDir . '/wp-starter-plugin.php';
		self::$filesystem->dumpFile($filePath, '');

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

					case 'project_name':
						return 'acme/awesome-plugin';

					case 'wp_plugin_name':
						return 'Acme Awesome Plugin';

					case 'wp_plugin_slug':
						return 'acme-awesome-plugin';

					default:
						return [
							'php_namespace' => 'Acme\AwesomePlugin',
							'vendor_prefix' => 'AAV\Lib',
							'project_name' => 'acme/awesome-plugin',
							'wp_plugin_name' => 'Acme Awesome Plugin',
							'wp_plugin_slug' => 'acme-awesome-plugin',
						];
				}
			}));

		$fileInfo = new SplFileInfo($filePath);
		$searchReplace = new SearchReplace($mock);
		$searchReplace->file($fileInfo);

		self::assertFileExists(self::$tempDir . '/acme-awesome-plugin.php');
	}

	public function dataExecute(): iterable
	{
		yield [
			'composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/wp-starter-plugin",
				"autoload": {
					"psr-4": {
						"WPStarterPlugin\\": "app/"
					}
				},
				"extra": {
					"syntatis": {
						"project": {
							"name": "wp-starter-plugin"
						}
					}
				}
			}
			CONTENT,
			<<<'CONTENT'
			{
			    "name": "acme/awesome-plugin",
			    "autoload": {
			        "psr-4": {
			            "Acme\\AwesomePlugin\\": "app/"
			        }
			    },
			    "extra": {
			        "syntatis": {
			            "project": {
			                "name": "wp-starter-plugin"
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
			    "name": "@syntatis/wp-starter-plugin",
			    "version": "1.0.0",
				"description": "A starting point for your next plugin project",
				"files": [
					"app",
					"dist-autoload",
					"dist",
					"inc",
					"index.php",
					"uninstall.php",
					"wp-starter-plugin.php"
				]
			}
			CONTENT,
			<<<'CONTENT'
			{
			    "name": "acme-awesome-plugin",
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

		yield [
			'foo.php',
			<<<'CONTENT'
			/**
			 * Plugin bootstrap file.
			 *
			 * This file is read by WordPress to display the plugin's information in the admin area.
			 *
			 * @wordpress-plugin
			 * Plugin Name:       WP Starter Plugin
			 * Plugin URI:        https://example.org
			 * Description:       This is a description of the plugin.
			 * Version:           1.0.0
			 * Requires at least: 5.2
			 * Requires PHP:      7.4
			 * Author:            John Doe
			 * Author URI:        https://example.org
			 * License:           GPL-2.0+
			 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
			 * Text Domain:       wp-starter-plugin
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
			 * Requires at least: 5.2
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

		yield 'scoper.inc.php' => [
			'scoper.inc.php',
			<<<'CONTENT'
			<?php
			declare(strict_types=1);

			use Isolated\Symfony\Component\Finder\Finder;

			return [
				'prefix' => 'WPStarterPlugin\\Vendor',
				'exclude-namespaces' => ['WPStarterPlugin'],
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

			namespace WPStarterPlugin;

			use RecursiveDirectoryIterator;
			use WPStarterPlugin\Vendor\Syntatis\WPHook\Contract\WithHook;
			use WPStarterPlugin\Vendor\Syntatis\WPHook\Hook;
			use WPStarterPlugin\HelloWorld\Plugin;
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
			window.__wpStarterPluginFoo = "bar";
			CONTENT,
			<<<'CONTENT'
			<?php
			window.__acmeAwesomePluginFoo = "bar";
			CONTENT,
		];

		yield [
			'wp-starter-plugin.pot',
			<<<'CONTENT'
			"Project-Id-Version: Foo Bar 1.0.0\n"
			"Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/wp-starter-plugin\n"
			"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
			"Language-Team: LANGUAGE <LL@li.org>\n"
			"MIME-Version: 1.0\n"
			"Content-Type: text/plain; charset=UTF-8\n"
			"Content-Transfer-Encoding: 8bit\n"
			"POT-Creation-Date: 2024-07-21T11:06:28+00:00\n"
			"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
			"X-Generator: WP-CLI 2.10.0\n"
			"X-Domain: wp-starter-plugin\n"
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
			CONTENT,
		];
	}
}
