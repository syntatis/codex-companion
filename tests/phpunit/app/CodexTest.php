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

	public function testMissingComposerFile(): void
	{
		$this->expectException(MissingRequiredFile::class);
		$this->expectExceptionMessageMatches('/.+\scomposer\.json$/');

		$codex = new Codex($this->getTemporaryPath());
	}

	public function testEmptyComposerFile(): void
	{
		$this->dumpTemporaryFile('/composer.json', '');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Invalid composer.json content');

		$codex = new Codex($this->getTemporaryPath());
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

		$this->dumpTemporaryFile('/composer.json', $content);

		$codex = new Codex($this->getTemporaryPath());

		$this->assertSame('syntatis/howdy', $codex->getComposer('name'));
		$this->assertSame('syntatis/howdy', $codex->getProjectName());
	}

	public function testGetRequire(): void
	{
		$this->dumpTemporaryFile(
			'/composer.json',
			json_encode([
				'require' => [
					'php' => '>=7.4',
					'ext-json' => '*',
				],
			]),
		);

		$codex = new Codex($this->getTemporaryPath());

		$this->assertSame(
			['php' => '>=7.4', 'ext-json' => '*'],
			$codex->getComposer('require'),
		);
	}

	public function testGetRequireNotSet(): void
	{
		$this->dumpTemporaryFile('/composer.json', json_encode([]));

		$codex = new Codex($this->getTemporaryPath());

		$this->assertNull($codex->getComposer('require'));
	}

	public function testGetRequireEmpty(): void
	{
		$this->dumpTemporaryFile('/composer.json', json_encode(['require' => []]));

		$codex = new Codex($this->getTemporaryPath());

		$this->assertSame([], $codex->getComposer('require'));
	}

	public function testGetRequireDev(): void
	{
		$this->dumpTemporaryFile(
			'/composer.json',
			json_encode([
				'require' => [
					'php' => '>=7.4',
					'ext-json' => '*',
				],
				'require-dev' => ['phpunit/phpunit' => '^9.5'],
			]),
		);

		$codex = new Codex($this->getTemporaryPath());

		$this->assertSame(
			['phpunit/phpunit' => '^9.5'],
			$codex->getComposer('require-dev'),
		);
	}

	public function testGetRequireDevEmpty(): void
	{
		$this->dumpTemporaryFile(
			'/composer.json',
			json_encode([
				'require' => [
					'php' => '>=7.4',
					'ext-json' => '*',
				],
				'require-dev' => [],
			]),
		);

		$codex = new Codex($this->getTemporaryPath());

		$this->assertSame([], $codex->getComposer('require-dev'));
	}

	public function testGetRequireDevNotSet(): void
	{
		$this->dumpTemporaryFile(
			'/composer.json',
			json_encode([
				'require' => [
					'php' => '>=7.4',
					'ext-json' => '*',
				],
			]),
		);

		$codex = new Codex($this->getTemporaryPath());

		$this->assertNull($codex->getComposer('require-dev'));
	}

	public function testGetAutoloadDev(): void
	{
		$this->dumpTemporaryFile(
			'/composer.json',
			json_encode([
				'autoload-dev' => [
					'psr-4' => ['Tests\\' => 'tests/'],
				],
			]),
		);

		$codex = new Codex($this->getTemporaryPath());

		$this->assertSame(
			['psr-4' => ['Tests\\' => 'tests/']],
			$codex->getComposer('autoload-dev'),
		);
	}

	public function testGetAutoloadDevNotSet(): void
	{
		$this->dumpTemporaryFile('/composer.json', json_encode([
			'autoload' => [
				'psr-4' => ['Codex\\' => 'app/'],
			],
		]));

		$codex = new Codex($this->getTemporaryPath());

		$this->assertNull($codex->getComposer('autoload-dev'));
	}

	public function testGetAutoloadDevEmpty(): void
	{
		$this->dumpTemporaryFile('/composer.json', json_encode(['autoload-dev' => []]));

		$codex = new Codex($this->getTemporaryPath());

		$this->assertSame([], $codex->getComposer('autoload-dev'));
	}

	public function testGetConfigOutputPathDefault(): void
	{
		$this->dumpTemporaryFile('/composer.json', json_encode([
			'extra' => [
				'codex' => [
					'scoper' => [],
				],
			],
		]));

		$codex = new Codex($this->getTemporaryPath());

		$this->assertSame(
			[
				'scoper' => [
					'output-dir' => $this->getTemporaryPath('/dist/autoload'),
				],
			],
			$codex->getConfig(),
		);
		$this->assertSame(
			$this->getTemporaryPath('/dist/autoload'),
			$codex->getConfig('scoper.output-dir'),
		);
	}

	public function testGetConfigOutputPathInvalidValue(): void
	{
		$this->dumpTemporaryFile('/composer.json', json_encode([
			'extra' => [
				'codex' => [
					'scoper' => ['output-dir' => null],
				],
			],
		]));

		$this->expectException(InvalidOptionsException::class);

		$codex = new Codex($this->getTemporaryPath());
	}

	/** @dataProvider dataGetConfigOutputPathRelative */
	public function testGetConfigOutputPathRelative(string $path): void
	{
		$this->dumpTemporaryFile('/composer.json', json_encode([
			'extra' => [
				'codex' => [
					'scoper' => ['output-dir' => $path],
				],
			],
		]));

		$codex = new Codex($this->getTemporaryPath());

		$this->assertSame(
			Path::makeAbsolute($path, $this->getTemporaryPath()),
			$codex->getConfig('scoper.output-dir'),
		);
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
		$this->dumpTemporaryFile('/composer.json', json_encode([
			'extra' => [
				'codex' => [
					'scoper' => ['output-dir' => '/absolute-path'],
				],
			],
		]));

		$codex = new Codex($this->getTemporaryPath());

		$this->assertSame(
			'/absolute-path',
			$codex->getConfig('scoper.output-dir'),
		);
	}

	public function testGetConfigInvalidKey(): void
	{
		$this->dumpTemporaryFile('/composer.json', json_encode([
			'extra' => [
				'codex' => [
					'scoper' => ['output-dir' => 'dist-autoload'],
				],
			],
		]));

		$codex = new Codex($this->getTemporaryPath());

		$this->assertNull($codex->getConfig('foo'));
	}
}
