<?php

declare(strict_types=1);

namespace Syntatis\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Helpers\WPPluginProps;
use Syntatis\Tests\WithTemporaryFiles;

use function json_encode;

class WPPluginPropsTest extends TestCase
{
	use WithTemporaryFiles;

	protected function setUp(): void
	{
		parent::setUp();

		$this->dumpTemporaryFile('composer.json', json_encode(['name' => 'project/name']));
	}

	/**
	 * @dataProvider dataGetSlug
	 *
	 * @param mixed $expect
	 */
	public function testGetSlug(array $files, $expect): void
	{
		foreach ($files as $filename => $content) {
			$this->dumpTemporaryFile($filename, $content);
		}

		$codex = new Codex($this->getTemporaryPath());
		$props = new WPPluginProps($codex);

		$this->assertSame($expect, $props->getSlug());
	}

	public static function dataGetSlug(): iterable
	{
		yield 'single file' => [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: 1.0.0
				CONTENT,
			],
			'expect' => 'main-plugin-file',
		];

		yield 'multiple php files' => [
			'files' => [
				'bar.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Bar Plugin
				 * Plugin URI: https://example.org
				 * Description: This is a description of the plugin.
				 */
				CONTENT,
				// Empty.
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: 1.0.0
				CONTENT,
			],
			'expect' => 'bar',
		];

		yield 'not kebabcase' => [
			'files' => [
				'foo_bar.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Foo Bar Plugin
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: 1.0.0
				CONTENT,
			],
			'expect' => 'foo-bar',
		];

		yield 'possibly duplicated main file' => [
			'files' => [
				'foo1.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Foo 1 Plugin
				 */
				CONTENT,
				'foo2.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Foo 2 Plugin
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: 1.0.0
				CONTENT,
			],
			'expect' => 'foo1',
		];
	}

	/**
	 * @dataProvider dataGetName
	 *
	 * @param mixed $expect
	 */
	public function testGetName(array $files, $expect): void
	{
		foreach ($files as $filename => $content) {
			$this->dumpTemporaryFile($filename, $content);
		}

		$codex = new Codex($this->getTemporaryPath());
		$props = new WPPluginProps($codex);

		$this->assertSame($expect, $props->getName());
	}

	public static function dataGetName(): iterable
	{
		yield 'default' => [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: 1.0.0
				CONTENT,
			],
			'expect' => 'Plugin Name',
		];

		yield 'edited' => [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Awesome Plugin
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: 1.0.0
				CONTENT,
			],
			'expect' => 'Awesome Plugin',
		];

		yield 'has spaces' => [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name:        Foo Plugin
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: 1.0.0
				CONTENT,
			],
			'expect' => 'Foo Plugin',
		];

		yield 'possibly duplicated header' => [
			'files' => [
				'foo-bar.php' => <<<'CONTENT'
				/**
				 * Plugin Name:        Foo Plugin
				 * Plugin Name:        Bar Plugin
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: 1.0.0
				CONTENT,
			],
			'expect' => 'Foo Plugin',
		];

		yield 'possibly duplicated main file' => [
			'files' => [
				'foo1.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Foo1 Plugin
				 */
				CONTENT,
				'foo2.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Foo2 Plugin
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: 1.0.0
				CONTENT,
			],
			'expect' => 'Foo1 Plugin',
		];
	}

	public function testGetNameEmpty(): void
	{
		$this->dumpTemporaryFile('foo3.php', '');

		$codex = new Codex($this->getTemporaryPath());

		$this->expectException(RuntimeException::class);

		new WPPluginProps($codex);
	}

	/**
	 * @dataProvider dataGetDescription
	 *
	 * @param mixed $expect
	 */
	public function testGetDescription(array $files, $expect): void
	{
		foreach ($files as $filename => $content) {
			$this->dumpTemporaryFile($filename, $content);
		}

		$codex = new Codex($this->getTemporaryPath());
		$props = new WPPluginProps($codex);

		$this->assertSame($expect, $props->getDescription());
	}

	public static function dataGetDescription(): iterable
	{
		yield [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 * Description: The plugin short description.
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: 1.0.0
				CONTENT,
			],
			'expect' => 'The plugin short description.',
		];

		yield [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 * Description: Awesome plugin.
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: 1.0.0
				CONTENT,
			],
			'expect' => 'Awesome plugin.',
		];
	}

	/**
	 * @dataProvider dataGetVersion
	 *
	 * @param mixed $expect
	 */
	public function testGetVersion(array $files, $expect): void
	{
		foreach ($files as $filename => $content) {
			$this->dumpTemporaryFile($filename, $content);
		}

		$codex = new Codex($this->getTemporaryPath());
		$props = new WPPluginProps($codex);

		$this->assertSame($expect, (string) $props->getVersion('wp_plugin_version'));
	}

	public static function dataGetVersion(): iterable
	{
		yield 'normal' => [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: 1.0.0
				CONTENT,
			],
			'expect' => '1.0.0',
		];

		yield 'without patch version' => [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: 1.0
				CONTENT,
			],
			'expect' => '1.0.0',
		];

		yield 'with v* prefix' => [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: v1.1.0
				CONTENT,
			],
			'expect' => '1.1.0', // Version will be normalized without the `v` prefix.
		];

		yield 'with v* prefix, and without patch version' => [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: v1.1
				CONTENT,
			],
			'expect' => '1.1.0', // Version will be normalized without the `v` prefix.
		];

		yield 'with spaces between colon' => [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag:    1.2.0
				CONTENT,
			],
			'expect' => '1.2.0',
		];

		yield 'with complete headers' => [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				=== Plugin Name ===

				Contributors: tfirdaus
				Tags: wordpress, plugin, boilerplate
				Tested up to: 6.6
				Stable tag: 0.1
				License: GPLv2 or later
				License URI: https://www.gnu.org/licenses/gpl-2.0.html

				The plugin short description.

				== Description ==

				The plugin long description.
				CONTENT,
			],
			'expect' => '0.1.0',
		];

		yield 'with complete headers, & prefixed with "v*"' => [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				=== Plugin Name ===

				Contributors: tfirdaus
				Tags: wordpress, plugin, boilerplate
				Tested up to: 6.6
				Stable tag: v0.1
				License: GPLv2 or later
				License URI: https://www.gnu.org/licenses/gpl-2.0.html

				The plugin short description.

				== Description ==

				The plugin long description.
				CONTENT,
			],
			'expect' => '0.1.0',
		];
	}

	/**
	 * @dataProvider dataGetVersionTestedUpTo
	 * @group test-here
	 *
	 * @param mixed $expect
	 */
	public function testGetVersionTestedUpto(array $files, $expect): void
	{
		foreach ($files as $filename => $content) {
			$this->dumpTemporaryFile($filename, $content);
		}

		$codex = new Codex($this->getTemporaryPath());
		$props = new WPPluginProps($codex);

		$this->assertSame($expect, (string) $props->getVersion('wp_plugin_tested_up_to'));
	}

	public static function dataGetVersionTestedUpTo(): iterable
	{
		yield 'normal' => [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 * Version: 1.0.2
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Tested up to: 6.6
				Stable tag: 1.0
				CONTENT,
			],
			'expect' => '6.6',
		];

		yield 'with version' => [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 * Version: 1.0.2
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Tested up to: v6.6
				Stable tag: 1.0
				CONTENT,
			],
			'expect' => '6.6',
		];
	}

	/**
	 * @dataProvider dataGetVersionRequiresMin
	 *
	 * @param mixed $expect
	 */
	public function testGetVersionRequiresMin(array $files, $expect): void
	{
		foreach ($files as $filename => $content) {
			$this->dumpTemporaryFile($filename, $content);
		}

		$codex = new Codex($this->getTemporaryPath());
		$props = new WPPluginProps($codex);

		$this->assertSame($expect, (string) $props->getVersion('wp_plugin_requires_at_least'));
	}

	public static function dataGetVersionRequiresMin(): iterable
	{
		yield 'normal' => [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 * Version: 1.0.2
				 * Requires at least: 5.8
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: 1.0
				CONTENT,
			],
			'expect' => '5.8',
		];

		yield 'with v* prefix' => [
			'files' => [
				'main-plugin-file.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 * Version: 1.0.2
				 * Requires at least: v5.8
				 */
				CONTENT,
				'readme.txt' => <<<'CONTENT'
				Stable tag: 1.0.2
				CONTENT,
			],
			'expect' => '5.8',
		];
	}
}
