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

	protected function setUp(): void
	{
		parent::setUp();

		$this->dumpTemporaryFile('composer.json', json_encode(['name' => 'project/name']));
	}

	/** @dataProvider dataGetNamespace */
	public function testGetNamespace(array $data, ?string $expect): void
	{
		// This will override the default composer.json file created in `setUp`.
		$this->dumpTemporaryFile('composer.json', json_encode($data));

		$codex = new Codex($this->getTemporaryPath());
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
		$this->dumpTemporaryFile('composer.json', json_encode($content));

		$codex = new Codex($this->getTemporaryPath());
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
