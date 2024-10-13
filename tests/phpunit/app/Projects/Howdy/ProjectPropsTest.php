<?php

declare(strict_types=1);

namespace Syntatis\Tests\Projects\Howdy;

use PHPUnit\Framework\TestCase;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Projects\Howdy\ProjectProps;
use Syntatis\Tests\WithTemporaryFiles;

use function json_encode;

class ProjectPropsTest extends TestCase
{
	use WithTemporaryFiles;

	public function setUp(): void
	{
		parent::setUp();

		self::setUpTemporaryPath();
		self::dumpTemporaryFile('/composer.json', json_encode(['name' => 'project/name']));
	}

	public function tearDown(): void
	{
		parent::tearDown();

		self::tearDownTemporaryPath();
	}

	/**
	 * @dataProvider dataGetSlug
	 *
	 * @param mixed $expect
	 */
	public function testGetSlug(array $files, $expect): void
	{
		foreach ($files as $filename => $content) {
			self::dumpTemporaryFile($filename, $content);
		}

		$codex = new Codex(self::getTemporaryPath());
		$projectProps = new ProjectProps($codex);

		$this->assertSame($expect, $projectProps->getPluginSlug());
	}

	public static function dataGetSlug(): iterable
	{
		yield 'single file' => [
			'files' => [
				'/foo.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 */
				CONTENT,
			],
			'expect' => 'foo',
		];

		yield 'multiple files' => [
			'files' => [
				'/bar.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Bar Plugin
				 * Plugin URI: https://example.org
				 * Description: This is a description of the plugin.
				 */
				CONTENT,
				// Empty.
				'/foo.php' => <<<'CONTENT'
				/**
				 */
				CONTENT,
			],
			'expect' => 'bar',
		];

		yield 'not kebabcase' => [
			'files' => [
				'/foo_bar.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Foo Bar Plugin
				 */
				CONTENT,
			],
			'expect' => 'foo-bar',
		];

		yield 'possibly duplicated main file' => [
			'files' => [
				'/foo1.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Foo 1 Plugin
				 */
				CONTENT,
				'/foo2.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Foo 2 Plugin
				 */
				CONTENT,
			],
			'expect' => 'foo1',
		];

		yield 'no file' => [
			'files' => [],
			'expect' => null,
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
			self::dumpTemporaryFile($filename, $content);
		}

		$codex = new Codex(self::getTemporaryPath());
		$projectProps = new ProjectProps($codex);

		$this->assertSame($expect, $projectProps->getPluginName());
	}

	public static function dataGetName(): iterable
	{
		yield 'default' => [
			'files' => [
				'/foo.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 */
				CONTENT,
			],
			'expect' => 'Plugin Name',
		];

		yield 'edited' => [
			'files' => [
				'/foo.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Awesome Plugin
				 */
				CONTENT,
			],
			'expect' => 'Awesome Plugin',
		];

		yield 'has spaces' => [
			'files' => [
				'/foo.php' => <<<'CONTENT'
				/**
				 * Plugin Name:        Foo Plugin
				 */
				CONTENT,
			],
			'expect' => 'Foo Plugin',
		];

		yield 'possibly duplicated header' => [
			'files' => [
				'/foo-bar.php' => <<<'CONTENT'
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
				'/foo1.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Foo1 Plugin
				 */
				CONTENT,
				'/foo2.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Foo2 Plugin
				 */
				CONTENT,
			],
			'expect' => 'Foo1 Plugin',
		];
	}

	/**
	 * @dataProvider dataGetDescription
	 *
	 * @param mixed $expect
	 */
	public function testGetDescription(array $files, $expect): void
	{
		foreach ($files as $filename => $content) {
			self::dumpTemporaryFile($filename, $content);
		}

		$codex = new Codex(self::getTemporaryPath());
		$projectProps = new ProjectProps($codex);

		$this->assertSame($expect, $projectProps->getPluginDescription());
	}

	public static function dataGetDescription(): iterable
	{
		yield [
			'files' => [
				'/foo.php' => <<<'CONTENT'
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
				'/foo.php' => <<<'CONTENT'
				/**
				 * Plugin Name: Plugin Name
				 * Description: Awesome plugin.
				 */
				CONTENT,
			],
			'expect' => 'Awesome plugin.',
		];

		yield [
			'files' => [
				'/foo.php' => <<<'CONTENT'
				/**
				 * Description: Awesome plugin.
				 */
				CONTENT,
			],
			'expect' => null,
		];
	}

	/** @dataProvider dataGetNamespace */
	public function testGetNamespace(array $data, ?string $expect): void
	{
		// This will override the default composer.json file created in `setUp`.
		self::dumpTemporaryFile('/composer.json', json_encode($data));

		$codex = new Codex(self::getTemporaryPath());
		$projectProps = new ProjectProps($codex);

		$this->assertSame($expect, $projectProps->getNamespace());
	}

	public static function dataGetNamespace(): iterable
	{
		yield 'with app/' => [
			'data' => [
				'autoload' => [
					'psr-4' => ['Namespace\\' => 'app/'],
				],
			],
			'expect' => 'Namespace',
		];

		yield 'with app/ and nested namespace' => [
			'data' => [
				'autoload' => [
					'psr-4' => ['Namespace\\Foo\\' => 'app/'],
				],
			],
			'expect' => 'Namespace\Foo',
		];

		yield 'with app/ and other namespace' => [
			'data' => [
				'autoload' => [
					'psr-4' => [
						'Namespace\\' => 'app/',
						'Foo\\' => 'inc/',
					],
				],
			],
			'expect' => 'Namespace',
		];

		yield 'empty autoload' => [
			'data' => [],
			'expect' => null,
		];

		yield 'empty psr-4' => [
			'data' => [
				'autoload' => [
					'psr-4' => [],
				],
			],
			'expect' => null,
		];

		yield 'no app/' => [
			'data' => [
				'autoload' => [
					'psr-4' => ['Foo\\' => 'inc/'],
				],
			],
			'expect' => null,
		];

		yield 'array list dirs' => [
			'data' => [
				'autoload' => [
					'psr-4' => ['Bar\\' => ['app/', 'inc/']],
				],
			],
			'expect' => 'Bar',
		];
	}

	/**
	 * @dataProvider dataGetVendorPrefix
	 *
	 * @param mixed $expect
	 */
	public function testGetVendorPrefix(array $content, $expect): void
	{
		self::dumpTemporaryFile('/composer.json', json_encode($content));

		$codex = new Codex(self::getTemporaryPath());
		$projectProps = new ProjectProps($codex);

		$this->assertSame($expect, $projectProps->getVendorPrefix());
	}

	public static function dataGetVendorPrefix(): iterable
	{
		yield 'has prefix' => [
			'content' => [
				'extra' => [
					'codex' => [
						'scoper' => ['prefix' => 'Foo\\Vendor'],
					],
				],
			],
			'expect' => 'Foo\\Vendor',
		];

		yield 'has no prefix' => [
			'content' => [
				'extra' => [
					'codex' => [
						'scoper' => ['prefix' => ''],
					],
				],
			],
			'expect' => null,
		];

		yield 'blank' => [
			'content' => [
				'extra' => [],
			],
			'expect' => null,
		];
	}
}
