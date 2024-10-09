<?php

declare(strict_types=1);

namespace Syntatis\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
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
		self::createTemporaryFile('/composer.json', '');

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

		self::createTemporaryFile('/composer.json', $content);

		$codex = new Codex(self::getTemporaryPath());

		$this->assertSame('syntatis/howdy', $codex->getComposer()->get('name'));
		$this->assertSame('syntatis/howdy', $codex->getName());
	}

	public function testGetRequire(): void
	{
		self::createTemporaryFile(
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
			(new Codex(self::getTemporaryPath()))->getComposer()->get('require'),
		);
	}

	public function testGetRequireNotSet(): void
	{
		self::createTemporaryFile('/composer.json', json_encode([]));

		$this->assertNull((new Codex(self::getTemporaryPath()))->getComposer()->get('require'));
	}

	public function testGetRequireEmpty(): void
	{
		self::createTemporaryFile('/composer.json', json_encode(['require' => []]));

		$codex = new Codex(self::getTemporaryPath());

		$this->assertSame([], $codex->getComposer()->get('require'));
	}

	public function testGetRequireDev(): void
	{
		self::createTemporaryFile('/composer.json', json_encode([
			'require' => [
				'php' => '>=7.4',
				'ext-json' => '*',
			],
			'require-dev' => ['phpunit/phpunit' => '^9.5'],
		]));

		$this->assertSame(
			['phpunit/phpunit' => '^9.5'],
			(new Codex(self::getTemporaryPath()))->getComposer()->get('require-dev'),
		);
	}

	public function testGetRequireDevEmpty(): void
	{
		self::createTemporaryFile('/composer.json', json_encode([
			'require' => [
				'php' => '>=7.4',
				'ext-json' => '*',
			],
			'require-dev' => [],
		]));

		$this->assertSame([], (new Codex(self::getTemporaryPath()))->getComposer()->get('require-dev'));
	}

	public function testGetRequireDevNotSet(): void
	{
		self::createTemporaryFile('/composer.json', json_encode([
			'require' => [
				'php' => '>=7.4',
				'ext-json' => '*',
			],
		]));

		$this->assertNull((new Codex(self::getTemporaryPath()))->getComposer()->get('require-dev'));
	}

	public function testGetAutoloadDev(): void
	{
		self::createTemporaryFile('/composer.json', json_encode([
			'autoload-dev' => [
				'psr-4' => ['Tests\\' => 'tests/'],
			],
		]));

		$this->assertSame(
			['psr-4' => ['Tests\\' => 'tests/']],
			(new Codex(self::getTemporaryPath()))->getComposer()->get('autoload-dev'),
		);
	}

	public function testGetAutoloadDevNotSet(): void
	{
		self::createTemporaryFile('/composer.json', json_encode([
			'autoload' => [
				'psr-4' => ['Codex\\' => 'app/'],
			],
		]));

		$this->assertNull((new Codex(self::getTemporaryPath()))->getComposer()->get('autoload-dev'));
	}

	public function testGetAutoloadDevEmpty(): void
	{
		self::createTemporaryFile('/composer.json', json_encode(['autoload-dev' => []]));

		$codex = new Codex(self::getTemporaryPath());

		$this->assertSame([], $codex->getComposer()->get('autoload-dev'));
	}

	public function testGetConfig(): void
	{
		self::createTemporaryFile('/composer.json', json_encode([
			'extra' => [
				'codex' => [
					'scoper' => ['output-path' => 'dist-autoload'],
				],
			],
		]));

		$codex = new Codex(self::getTemporaryPath());

		$this->assertSame(['scoper' => ['output-path' => 'dist-autoload']], $codex->getConfig());
		$this->assertSame('dist-autoload', $codex->getConfig('scoper.output-path'));
		$this->assertNull($codex->getConfig('foo'));
	}
}
