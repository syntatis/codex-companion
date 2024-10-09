<?php

declare(strict_types=1);

namespace Syntatis\Tests\Projects\Howdy;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Console\ProjectInitCommand\Howdy\UserInputs;
use Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles;
use Syntatis\Codex\Companion\Projects\Howdy\ProjectProps;
use Syntatis\Tests\WithTemporaryFiles;

use function file_get_contents;
use function str_replace;

use const PHP_EOL;

class InitializeFilesTest extends TestCase
{
	use WithTemporaryFiles;

	public function setUp(): void
	{
		parent::setUp();

		self::setUpTemporaryPath();
	}

	public function tearDown(): void
	{
		self::tearDownTemporaryPath();

		parent::tearDown();
	}

	/** @dataProvider dataAll */
	public function testAll(array $files, array $inputs): void
	{
		/**
		 * ========================================================================
		 * Setups
		 * ========================================================================
		 */

		foreach ($files as $filename => $content) {
			self::createTemporaryFile($filename, $content['origin']);
		}

		$codex = new Codex(self::getTemporaryPath());
		$projectProps = new ProjectProps($codex);
		$userInputs = new UserInputs($projectProps->getAll());

		/** @var StyleInterface&MockObject $style */
		$style = $this->getMockBuilder(SymfonyStyle::class)
			->disableOriginalConstructor()
			->getMock();
		$style
			->method('ask')
			->will(self::returnCallback(static function ($param, $default, $callback) use ($inputs) {
				return $callback($inputs[$param]);
			}));

		$userInputs->execute($style);
		$instance = new InitializeFiles(
			$userInputs->getProjectProps(),
			$userInputs->getInputs(),
		);

		/**
		 * ========================================================================
		 * Actions
		 * ========================================================================
		 */

		foreach ($files as $filename => $content) {
			$instance->file(new SplFileInfo(self::getTemporaryPath($filename)));
		}

		/**
		 * ========================================================================
		 * Assertions
		 * ========================================================================
		 */

		foreach ($files as $filename => $content) {
			switch ($filename) {
				case '/plugin-name.php':
					$editedContent = file_get_contents(self::getTemporaryPath('/' . $inputs['Plugin slug'] . '.php'));
					break;

				case '/plugin-name.pot':
					$editedContent = file_get_contents(self::getTemporaryPath('/' . $inputs['Plugin slug'] . '.pot'));
					break;

				default:
					$editedContent = file_get_contents(self::getTemporaryPath($filename));
					break;
			}

			$this->assertEquals(
				str_replace("\r\n", "\n", $content['expect']),
				str_replace(PHP_EOL, "\n", $editedContent),
			);
		}
	}

	public static function dataAll(): iterable
	{
		yield [
			'files' => [
				'/composer.json' => [
					'origin' => <<<'CONTENT'
					{
						"name": "syntatis/howdy",
						"autoload": {
							"psr-4": {
								"PluginName\\": "app/",
								"PluginName\\Lib\\": ["inc/", "etc/"],
								"FooPluginName\\": "src/",
								"PluginNameFoo\\": "foo/",
								"Bar\\PluginName\\": "bar/"
							}
						},
						"extra": {
							"codex": {
								"scoper": {
									"prefix": "PluginName\\Vendor",
									"exclude-namespaces": ["PluginName", "Whoops"]
								}
							}
						}
					}
					CONTENT,
					'expect' => <<<'CONTENT'
					{
					    "name": "syntatis/howdy",
					    "autoload": {
					        "psr-4": {
					            "Awesome\\PluginName\\": "app/",
					            "Awesome\\PluginName\\Lib\\": [
					                "inc/",
					                "etc/"
					            ],
					            "FooPluginName\\": "src/",
					            "PluginNameFoo\\": "foo/",
					            "Bar\\PluginName\\": "bar/"
					        }
					    },
					    "extra": {
					        "codex": {
					            "scoper": {
					                "prefix": "AW\\Vendor",
					                "exclude-namespaces": [
					                    "Awesome\\PluginName",
					                    "Whoops"
					                ]
					            }
					        }
					    }
					}
					CONTENT,
				],
				'/package.json' => [
					'origin' => <<<'CONTENT'
					{
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
					'expect' => <<<'CONTENT'
					{
					    "files": [
					        "app",
					        "dist-autoload",
					        "dist",
					        "inc",
					        "index.php",
					        "uninstall.php",
					        "awesome-plugin-name.php"
					    ]
					}
					CONTENT,
				],
				'/plugin-name.php' => [
					'origin' => <<<'CONTENT'
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
					'expect' => <<<'CONTENT'
					/**
					 * Plugin bootstrap file.
					 *
					 * This file is read by WordPress to display the plugin's information in the admin area.
					 *
					 * @wordpress-plugin
					 * Plugin Name:       Awesome Plugin Name
					 * Plugin URI:        https://example.org
					 * Description:       Awesome plugin description.
					 * Version:           1.0.0
					 * Requires at least: 5.8
					 * Requires PHP:      7.4
					 * Author:            Author Name
					 * Author URI:        https://example.org
					 * License:           GPL-2.0+
					 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
					 * Text Domain:       awesome-plugin-name
					 * Domain Path:       /inc/languages
					 */
					CONTENT,
				],
				'/foo.php' => [
					'origin' => <<<'CONTENT'
					<?php
					declare(strict_types=1);

					namespace PluginName;

					use RecursiveDirectoryIterator;
					use PluginName\Vendor\PluginName\UUID;
					use PluginName\Vendor\Codex\Hook;
					use \PluginName\HelloWorld\Plugin;
					CONTENT,
					'expect' => <<<'CONTENT'
					<?php
					declare(strict_types=1);

					namespace Awesome\PluginName;

					use RecursiveDirectoryIterator;
					use AW\Vendor\PluginName\UUID;
					use AW\Vendor\Codex\Hook;
					use \Awesome\PluginName\HelloWorld\Plugin;
					CONTENT,
				],
				'/foo.js' => [
					'origin' => <<<'CONTENT'
					<?php
					window.__pluginName = "bar";
					CONTENT,
					'expect' => <<<'CONTENT'
					<?php
					window.__awesomePluginName = "bar";
					CONTENT,
				],
				'/readme.txt' => [
					'origin' => <<<'CONTENT'
					=== Plugin Name ===

					Contributors: tfirdaus
					Tags: wordpress, plugin, boilerplate
					Requires at least: 5.8
					Tested up to: 6.6
					Stable tag: 1.0
					Requires PHP: 7.4
					License: GPLv2 or later
					License URI: https://www.gnu.org/licenses/gpl-2.0.html

					The plugin short description.

					== Description ==

					The plugin long description.
					CONTENT,
					'expect' => <<<'CONTENT'
					=== Awesome Plugin Name ===

					Contributors: tfirdaus
					Tags: wordpress, plugin, boilerplate
					Requires at least: 5.8
					Tested up to: 6.6
					Stable tag: 1.0
					Requires PHP: 7.4
					License: GPLv2 or later
					License URI: https://www.gnu.org/licenses/gpl-2.0.html

					Awesome plugin description.

					== Description ==

					The plugin long description.
					CONTENT,
				],
				'/plugin-name.pot' => [
					'origin' => <<<'CONTENT'
					# Copyright (C) 2024 Author Name
					# This file is distributed under the GPL-2.0+.
					msgid ""
					msgstr ""
					"Project-Id-Version: Plugin Name 1.0.0\n"
					"Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/howdy\n"
					"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
					"Language-Team: LANGUAGE <LL@li.org>\n"
					"MIME-Version: 1.0\n"
					"Content-Type: text/plain; charset=UTF-8\n"
					"Content-Transfer-Encoding: 8bit\n"
					"POT-Creation-Date: 2024-10-05T17:00:12+00:00\n"
					"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
					"X-Generator: WP-CLI 2.10.0\n"
					"X-Domain: plugin-name\n"

					#. Plugin Name of the plugin
					#: plugin-name.php
					msgid "Plugin Name"
					msgstr ""

					#. Plugin URI of the plugin
					#. Author URI of the plugin
					#: plugin-name.php
					msgid "https://example.org"
					msgstr ""
					CONTENT,
					'expect' => <<<'CONTENT'
					# Copyright (C) 2024 Author Name
					# This file is distributed under the GPL-2.0+.
					msgid ""
					msgstr ""
					"Project-Id-Version: Awesome Plugin Name 1.0.0\n"
					"Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/awesome-plugin-name\n"
					"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
					"Language-Team: LANGUAGE <LL@li.org>\n"
					"MIME-Version: 1.0\n"
					"Content-Type: text/plain; charset=UTF-8\n"
					"Content-Transfer-Encoding: 8bit\n"
					"POT-Creation-Date: 2024-10-05T17:00:12+00:00\n"
					"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
					"X-Generator: WP-CLI 2.10.0\n"
					"X-Domain: awesome-plugin-name\n"

					#. Plugin Name of the plugin
					#: awesome-plugin-name.php
					msgid "Awesome Plugin Name"
					msgstr ""

					#. Plugin URI of the plugin
					#. Author URI of the plugin
					#: awesome-plugin-name.php
					msgid "https://example.org"
					msgstr ""
					CONTENT,
				],
				'/block.json' => [
					'origin' => <<<'CONTENT'
					{
						"name": "plugin-name/static-block",
					}
					CONTENT,
					'expect' => <<<'CONTENT'
					{
						"name": "awesome-plugin-name/static-block",
					}
					CONTENT,
				],
				'/file.json' => [
					'origin' => <<<'CONTENT'
					{
						"name": "pluginName/foo",
					}
					CONTENT,
					'expect' => <<<'CONTENT'
					{
						"name": "awesomePluginName/foo",
					}
					CONTENT,
				],
				'/file.css' => [
					'origin' => <<<'CONTENT'
					.plugin-name-foo {
						color: red;
					}
					.pluginNameFoo {
						color: red;
					}
					CONTENT,
					'expect' => <<<'CONTENT'
					.awesome-plugin-name-foo {
						color: red;
					}
					.awesomePluginNameFoo {
						color: red;
					}
					CONTENT,
				],
				'/phpcs.xml' => [
					'origin' => <<<'CONTENT'
					<file>plugin-name.php</file>
					<rule ref="WordPress.WP.I18n">
						<properties>
							<property name="text_domain" type="array">
								<element value="plugin-name"/>
							</property>
						</properties>
					</rule>
					<rule ref="SlevomatCodingStandard.Files.TypeNameMatchesFileName">
						<properties>
							<property name="rootNamespaces" type="array">
								<element key="app" value="PluginName"/>
							</property>
						</properties>
					</rule>
					CONTENT,
					'expect' => <<<'CONTENT'
					<file>awesome-plugin-name.php</file>
					<rule ref="WordPress.WP.I18n">
						<properties>
							<property name="text_domain" type="array">
								<element value="awesome-plugin-name"/>
							</property>
						</properties>
					</rule>
					<rule ref="SlevomatCodingStandard.Files.TypeNameMatchesFileName">
						<properties>
							<property name="rootNamespaces" type="array">
								<element key="app" value="Awesome\PluginName"/>
							</property>
						</properties>
					</rule>
					CONTENT,
				],
				'/component.jsx' => [
					'origin' => <<<'CONTENT'
					<label
						htmlFor="plugin-name-settings-greeting"
						id="pluginNameSettingsGreetingLabel"
					>
						{ __( 'Greeting', 'plugin-name' ) }
					</label>
					<TextField
						isInvalid={
							errorMessages?.plugin_name_greeting ??
							false
						}
						errorMessage={
							errorMessages?.plugin_name_greeting
						}
						aria-labelledby="plugin-name-settings-greeting-label"
						id="plugin-name-settings-greeting"
						className="regular-text"
						defaultValue={ getOption(
							'plugin_name_greeting'
						) }
						name="plugin_name_greeting"
						description={ __(
							'Enter a greeting to display.',
							'plugin-name'
						) }
					/>
					CONTENT,
					'expect' => <<<'CONTENT'
					<label
						htmlFor="awesome-plugin-name-settings-greeting"
						id="awesomePluginNameSettingsGreetingLabel"
					>
						{ __( 'Greeting', 'awesome-plugin-name' ) }
					</label>
					<TextField
						isInvalid={
							errorMessages?.awesome_plugin_name_greeting ??
							false
						}
						errorMessage={
							errorMessages?.awesome_plugin_name_greeting
						}
						aria-labelledby="awesome-plugin-name-settings-greeting-label"
						id="awesome-plugin-name-settings-greeting"
						className="regular-text"
						defaultValue={ getOption(
							'awesome_plugin_name_greeting'
						) }
						name="awesome_plugin_name_greeting"
						description={ __(
							'Enter a greeting to display.',
							'awesome-plugin-name'
						) }
					/>
					CONTENT,
				],
			],
			'inputs' => [
				'PHP namespace' => 'Awesome\\PluginName',
				'PHP vendor prefix' => 'AW\\Vendor',
				'Plugin slug' => 'awesome-plugin-name',
				'Plugin name' => 'Awesome Plugin Name',
				'Plugin description' => 'Awesome plugin description.',
			],
		];
	}
}
