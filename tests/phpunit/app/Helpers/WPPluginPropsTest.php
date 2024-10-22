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
		$this->dumpTemporaryFile('readme.txt', <<<'CONTENT'
		=== Plugin Name ===

		Contributors: tfirdaus
		Tags: wordpress, plugin, boilerplate
		Tested up to: 6.6
		Stable tag: v0.1.0
		License: GPLv2 or later
		License URI: https://www.gnu.org/licenses/gpl-2.0.html

		The plugin short description.

		== Description ==

		The plugin long description.
		CONTENT);

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
				'foo.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 */
				CONTENT,
			],
			'expect' => 'foo',
		];

		yield 'multiple files' => [
			'files' => [
				'bar.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Bar Plugin
				 * Plugin URI: https://example.org
				 * Description: This is a description of the plugin.
				 */
				CONTENT,
				// Empty.
				'foo.php' => <<<'CONTENT'
				/**
				 */
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
				'foo.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 */
				CONTENT,
			],
			'expect' => 'Plugin Name',
		];

		yield 'edited' => [
			'files' => [
				'foo.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Awesome Plugin
				 */
				CONTENT,
			],
			'expect' => 'Awesome Plugin',
		];

		yield 'has spaces' => [
			'files' => [
				'foo.php' => <<<'CONTENT'
				/**
				 * Plugin Name:        Foo Plugin
				 */
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
				'foo.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 * Description: The plugin short description.
				 */
				CONTENT,
			],
			'expect' => 'The plugin short description.',
		];

		yield [
			'files' => [
				'foo.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 * Description: Awesome plugin.
				 */
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

		$this->assertSame($expect, $props->getVersion()->toString());
	}

	public static function dataGetVersion(): iterable
	{
		yield 'normal' => [
			'files' => [
				'foo.php' => <<<'CONTENT'
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
				'foo.php' => <<<'CONTENT'
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
				'foo.php' => <<<'CONTENT'
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
				'foo.php' => <<<'CONTENT'
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
				'foo.php' => <<<'CONTENT'
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
				'foo.php' => <<<'CONTENT'
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
}
