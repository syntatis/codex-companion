<?php

declare(strict_types=1);

namespace Syntatis\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Exceptions\MissingRequiredFile;

use function json_encode;

class CodexTest extends TestCase
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

	public function testMissingComposerFile(): void
	{
		$this->expectException(MissingRequiredFile::class);
		$this->expectExceptionMessageMatches('/.+\scomposer\.json$/');

		$codex = new Codex(self::getTemporaryPath());
	}

	public function testEmptyComposerFile(): void
	{
		self::dumpTemporaryFile('/composer.json', '');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Invalid composer.json content');

		$codex = new Codex(self::getTemporaryPath());
	}

	public function testGetName(): void
	{
		$content = json_encode([
			'name' => 'syntatis/howdy',
			'require' => [
				'php' => '>=7.4',
				'ext-json' => '*',
			],
		]);

		self::dumpTemporaryFile('/composer.json', $content);

		$codex = new Codex(self::getTemporaryPath());

		$this->assertSame('syntatis/howdy', $codex->getComposer('name'));
		$this->assertSame('syntatis/howdy', $codex->getProjectName());
	}

	public function testGetRequire(): void
	{
		self::dumpTemporaryFile(
			'/composer.json',
			json_encode([
				'require' => [
					'php' => '>=7.4',
					'ext-json' => '*',
				],
			]),
		);

		$this->assertSame(
			['php' => '>=7.4', 'ext-json' => '*'],
			(new Codex(self::getTemporaryPath()))->getComposer('require'),
		);
	}

	public function testGetRequireNotSet(): void
	{
		self::dumpTemporaryFile('/composer.json', json_encode([]));

		$this->assertNull((new Codex(self::getTemporaryPath()))->getComposer('require'));
	}

	public function testGetRequireEmpty(): void
	{
		self::dumpTemporaryFile('/composer.json', json_encode(['require' => []]));

		$codex = new Codex(self::getTemporaryPath());

		$this->assertSame([], $codex->getComposer('require'));
	}

	public function testGetRequireDev(): void
	{
		self::dumpTemporaryFile('/composer.json', json_encode([
			'require' => [
				'php' => '>=7.4',
				'ext-json' => '*',
			],
			'require-dev' => ['phpunit/phpunit' => '^9.5'],
		]));

		$this->assertSame(
			['phpunit/phpunit' => '^9.5'],
			(new Codex(self::getTemporaryPath()))->getComposer('require-dev'),
		);
	}

	public function testGetRequireDevEmpty(): void
	{
		self::dumpTemporaryFile('/composer.json', json_encode([
			'require' => [
				'php' => '>=7.4',
				'ext-json' => '*',
			],
			'require-dev' => [],
		]));

		$this->assertSame([], (new Codex(self::getTemporaryPath()))->getComposer('require-dev'));
	}

	public function testGetRequireDevNotSet(): void
	{
		self::dumpTemporaryFile('/composer.json', json_encode([
			'require' => [
				'php' => '>=7.4',
				'ext-json' => '*',
			],
		]));

		$this->assertNull((new Codex(self::getTemporaryPath()))->getComposer('require-dev'));
	}

	public function testGetAutoloadDev(): void
	{
		self::dumpTemporaryFile('/composer.json', json_encode([
			'autoload-dev' => [
				'psr-4' => ['Tests\\' => 'tests/'],
			],
		]));

		$this->assertSame(
			['psr-4' => ['Tests\\' => 'tests/']],
			(new Codex(self::getTemporaryPath()))->getComposer('autoload-dev'),
		);
	}

	public function testGetAutoloadDevNotSet(): void
	{
		self::dumpTemporaryFile('/composer.json', json_encode([
			'autoload' => [
				'psr-4' => ['Codex\\' => 'app/'],
			],
		]));

		$this->assertNull((new Codex(self::getTemporaryPath()))->getComposer('autoload-dev'));
	}

	public function testGetAutoloadDevEmpty(): void
	{
		self::dumpTemporaryFile('/composer.json', json_encode(['autoload-dev' => []]));

		$codex = new Codex(self::getTemporaryPath());

		$this->assertSame([], $codex->getComposer('autoload-dev'));
	}

	public function testGetConfigOutputPathDefault(): void
	{
		self::dumpTemporaryFile('/composer.json', json_encode([
			'extra' => [
				'codex' => [
					'scoper' => [],
				],
			],
		]));

		$codex = new Codex(self::getTemporaryPath());

		$this->assertSame(['scoper' => ['output-dir' => self::getTemporaryPath('/dist/autoload')]], $codex->getConfig());
		$this->assertSame(self::getTemporaryPath('/dist/autoload'), $codex->getConfig('scoper.output-dir'));
	}

	public function testGetConfigOutputPathInvalidValue(): void
	{
		self::dumpTemporaryFile('/composer.json', json_encode([
			'extra' => [
				'codex' => [
					'scoper' => ['output-dir' => null],
				],
			],
		]));

		$this->expectException(InvalidOptionsException::class);

		$codex = new Codex(self::getTemporaryPath());
	}

	/** @dataProvider dataGetConfigOutputPathRelative */
	public function testGetConfigOutputPathRelative(string $path): void
	{
		self::dumpTemporaryFile('/composer.json', json_encode([
			'extra' => [
				'codex' => [
					'scoper' => ['output-dir' => $path],
				],
			],
		]));

		$codex = new Codex(self::getTemporaryPath());

		$this->assertSame(Path::makeAbsolute($path, self::getTemporaryPath()), $codex->getConfig('scoper.output-dir'));
	}

	public static function dataGetConfigOutputPathRelative(): iterable
	{
		yield './relative-path' => ['./relative-path'];
		yield '../relative-path' => ['../relative-path'];
		yield 'relative-path/' => ['relative-path/'];
		yield 'relative-path' => ['relative-path'];
	}

	public function testGetConfigOutputPathAbsolute(): void
	{
		self::dumpTemporaryFile('/composer.json', json_encode([
			'extra' => [
				'codex' => [
					'scoper' => ['output-dir' => '/absolute-path'],
				],
			],
		]));

		$codex = new Codex(self::getTemporaryPath());

		$this->assertSame('/absolute-path', $codex->getConfig('scoper.output-dir'));
	}

	public function testGetConfigInvalidKey(): void
	{
		self::dumpTemporaryFile('/composer.json', json_encode([
			'extra' => [
				'codex' => [
					'scoper' => ['output-dir' => 'dist-autoload'],
				],
			],
		]));

		$codex = new Codex(self::getTemporaryPath());
		$this->assertNull($codex->getConfig('foo'));
	}
}
