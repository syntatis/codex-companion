<?php

declare(strict_types=1);

namespace Syntatis\Tests\Helpers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Helpers\PHPScoperFilesystem;
use Syntatis\Tests\WithTemporaryFiles;

use function file_get_contents;
use function json_decode;
use function json_encode;

use const JSON_UNESCAPED_SLASHES;

class PHPScoperFilesystemTest extends TestCase
{
	use WithTemporaryFiles;

	public function setUp(): void
	{
		parent::setUp();

		$this->dumpTemporaryFile(
			'composer.json',
			json_encode(
				[
					'name' => 'syntatis/howdy',
					'require' => ['php' => '>=7.4'],
					'autoload' => [
						'psr-4' => [
							'Syntatis\\' => 'src/',
							'Syntatis\\Lib\\' => ['lib/', 'ext/'],
						],
					],
					'autoload-dev' => [
						'psr-4' => ['Syntatis\\Tests\\' => 'tests/phpunit'],
					],
				],
				JSON_UNESCAPED_SLASHES,
			),
		);
	}

	public function testGetHash(): void
	{
		$codex = new Codex($this->getTemporaryPath());

		$this->assertMatchesRegularExpression(
			'/^[a-fA-F0-9]{32}$/',
			(new PHPScoperFilesystem($codex))->getHash(),
		);
	}

	/** @dataProvider dataGetBuildPath */
	public function testGetBuildPath(string $path, string $expect): void
	{
		$codex = new Codex($this->getTemporaryPath());
		$filesystem = new PHPScoperFilesystem($codex);

		$this->assertSame(
			$this->getTemporaryPath('dist/autoload-build-' . $filesystem->getHash() . '/' . $expect),
			$filesystem->getBuildPath($path),
		);
	}

	public static function dataGetBuildPath(): iterable
	{
		yield ['foo', 'foo'];
		yield ['./foo', 'foo'];
		yield ['foo/', 'foo'];
		yield ['foo//', 'foo'];
		yield ['foo///', 'foo'];
	}

	/** @dataProvider dataGetBuildPathInvalid */
	public function testGetBuildPathInvalid(string $path): void
	{
		$codex = new Codex($this->getTemporaryPath());
		$filesystem = new PHPScoperFilesystem($codex);

		$this->expectException(InvalidArgumentException::class);

		$filesystem->getBuildPath($path);
	}

	public static function dataGetBuildPathInvalid(): iterable
	{
		yield ['/foo'];
		yield ['//foo'];
		yield ['\foo'];
		yield ['../foo'];
		yield ['..foo'];
	}

	/** @dataProvider dataGetOutputPath */
	public function testGetOutputPath(string $path, string $expect): void
	{
		$codex = new Codex($this->getTemporaryPath());

		$this->assertSame(
			$this->getTemporaryPath('dist/autoload/' . $expect),
			(new PHPScoperFilesystem($codex))->getOutputPath($path),
		);
	}

	public function dataGetOutputPath(): iterable
	{
		yield ['foo', 'foo'];
		yield ['./foo', 'foo'];
		yield ['foo/', 'foo'];
		yield ['foo//', 'foo'];
		yield ['foo///', 'foo'];
	}

	/** @dataProvider dataGetOutputPathInvalid */
	public function testGetOutputPathInvalid(string $path): void
	{
		$codex = new Codex($this->getTemporaryPath());

		$this->expectException(InvalidArgumentException::class);

		(new PHPScoperFilesystem($codex))->getOutputPath($path);
	}

	public function dataGetOutputPathInvalid(): iterable
	{
		yield ['/foo'];
		yield ['//foo'];
		yield ['\foo'];
		yield ['../foo'];
		yield ['..foo'];
	}

	public function testGetOutputPathDefault(): void
	{
		$codex = new Codex($this->getTemporaryPath());

		$this->assertSame(
			$this->getTemporaryPath('dist/autoload'),
			(new PHPScoperFilesystem($codex))->getOutputPath(),
		);
	}

	public function testGetOutputPathCustom(): void
	{
		$codex = new Codex($this->getTemporaryPath());

		$this->dumpTemporaryFile('composer.json', json_encode([
			'name' => 'syntatis/howdy',
			'require' => ['php' => '>=7.4'],
			'extra' => [
				'codex' => [
					'scoper' => ['output-dir' => 'foo-autoload'],
				],
			],
		], JSON_UNESCAPED_SLASHES));

		$this->assertSame(
			$this->getTemporaryPath('foo-autoload'),
			(new PHPScoperFilesystem(new Codex($this->getTemporaryPath())))->getOutputPath(),
		);
	}

	public function testGetTemporaryPath(): void
	{
		$codex = new Codex($this->getTemporaryPath());
		$filesystem = new PHPScoperFilesystem($codex);

		$this->assertSame(
			$this->getTemporaryPath('dist/autoload-build-' . $filesystem->getHash()),
			$filesystem->getBuildPath(),
		);

		$this->assertSame(
			$this->getTemporaryPath('dist/autoload-build-' . $filesystem->getHash() . '/foo'),
			$filesystem->getBuildPath('foo'),
		);
	}

	public function testGetScoperConfig(): void
	{
		$codex = new Codex($this->getTemporaryPath());

		$this->assertSame(
			$this->getTemporaryPath('scoper.inc.php'),
			(new PHPScoperFilesystem($codex))->getConfigPath(),
		);
	}

	public function testDumpComposer(): void
	{
		$codex = new Codex($this->getTemporaryPath());
		$filesystem = new PHPScoperFilesystem($codex);

		$this->assertFileDoesNotExist($filesystem->getBuildPath('composer.json'));

		$filesystem->dumpComposerFile();

		$this->assertFileExists($filesystem->getBuildPath('composer.json'));

		$a = json_decode(file_get_contents($codex->getProjectPath('composer.json')), true);
		$b = json_decode(file_get_contents($filesystem->getBuildPath('composer.json')), true);

		$this->assertEquals($a['require'], $b['require']);
		$this->assertNotEmpty($a['require']);
		$this->assertNotEmpty($b['require']);
		$this->assertEquals(
			[
				'psr-4' => [
					'Syntatis\\' => '../../src',
					'Syntatis\\Lib\\' => ['../../lib', '../../ext'],
				],
			],
			$b['autoload'],
		);
		$this->assertEquals(
			[
				'psr-4' => ['Syntatis\\Tests\\' => '../../tests/phpunit'],
			],
			$b['autoload-dev'],
		);
	}

	public function testDumpComposerCustomScoperOutputDir(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
			json_encode(
				[
					'name' => 'syntatis/howdy',
					'require' => ['php' => '>=7.4'],
					'autoload' => [
						'psr-4' => [
							'Syntatis\\' => 'src/',
							'Syntatis\\Lib\\' => ['lib/', 'ext/'],
						],
					],
					'autoload-dev' => [
						'psr-4' => ['Syntatis\\Tests\\' => 'tests/phpunit'],
					],
					'extra' => [
						'codex' => [
							'scoper' => ['output-dir' => 'dist-autoload'],
						],
					],
				],
				JSON_UNESCAPED_SLASHES,
			),
		);

		$codex = new Codex($this->getTemporaryPath());
		$filesystem = new PHPScoperFilesystem($codex);

		$this->assertFileDoesNotExist($filesystem->getBuildPath('composer.json'));

		$filesystem->dumpComposerFile();

		$this->assertFileExists($filesystem->getBuildPath('composer.json'));

		$a = json_decode(file_get_contents($codex->getProjectPath('composer.json')), true);
		$b = json_decode(file_get_contents($filesystem->getBuildPath('composer.json')), true);

		$this->assertEquals($a['require'], $b['require']);
		$this->assertNotEmpty($a['require']);
		$this->assertNotEmpty($b['require']);
		$this->assertEquals(
			[
				'psr-4' => [
					'Syntatis\\' => '../src',
					'Syntatis\\Lib\\' => ['../lib', '../ext'],
				],
			],
			$b['autoload'],
		);
		$this->assertEquals(
			[
				'psr-4' => ['Syntatis\\Tests\\' => '../tests/phpunit'],
			],
			$b['autoload-dev'],
		);
	}

	public function testDumpComposerWithEmptyAutload(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
			json_encode(
				[
					'name' => 'syntatis/howdy',
					'require' => ['php' => '>=7.4'],
					'extra' => [
						'codex' => [
							'scoper' => ['output-dir' => 'foo-autoload'],
						],
					],
				],
				JSON_UNESCAPED_SLASHES,
			),
		);

		$codex = new Codex($this->getTemporaryPath());
		$filesystem = new PHPScoperFilesystem($codex);

		$this->assertFileDoesNotExist($filesystem->getBuildPath('composer.json'));

		$filesystem->dumpComposerFile();

		$a = json_decode(file_get_contents($codex->getProjectPath('composer.json')), true);
		$b = json_decode(file_get_contents($filesystem->getBuildPath('composer.json')), true);

		$this->assertArrayNotHasKey('autoload', $a);
		$this->assertArrayNotHasKey('autoload', $b);
	}

	public function testDumpComposerInstallDev(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
			json_encode(
				[
					'name' => 'syntatis/howdy',
					'require' => ['php' => '>=7.4'],
					'require-dev' => [
						'phpunit/phpunit' => '^9.5',
						'phpstan/phpstan' => '^1.0',
						'symfony/var-dumper' => '^5.3',
					],
					'extra' => [
						'codex' => [
							'scoper' => [
								'output-dir' => 'foo-autoload',
								'install-dev' => ['symfony/var-dumper'],
							],
						],
					],
				],
				JSON_UNESCAPED_SLASHES,
			),
		);

		$codex = new Codex($this->getTemporaryPath());
		$filesystem = new PHPScoperFilesystem($codex);
		$filesystem->dumpComposerFile();

		$a = json_decode(file_get_contents($codex->getProjectPath('composer.json')), true);
		$b = json_decode(file_get_contents($filesystem->getBuildPath('composer.json')), true);

		// a.
		$this->assertArrayHasKey('symfony/var-dumper', $a['require-dev']);
		$this->assertArrayHasKey('phpunit/phpunit', $a['require-dev']);
		$this->assertArrayHasKey('phpstan/phpstan', $a['require-dev']);

		// b.
		$this->assertArrayHasKey('symfony/var-dumper', $b['require-dev']);
		$this->assertArrayNotHasKey('phpunit/phpunit', $b['require-dev']);
		$this->assertArrayNotHasKey('phpstan/phpstan', $b['require-dev']);
	}

	public function testRemoveBuildPath(): void
	{
		$codex = new Codex($this->getTemporaryPath());
		$filesystem = new PHPScoperFilesystem($codex);
		$filesystem->dumpComposerFile();

		$this->assertDirectoryExists($filesystem->getBuildPath());

		$filesystem->removeBuildPath();

		$this->assertDirectoryDoesNotExist($filesystem->getBuildPath());
	}

	public function testRemoveAll(): void
	{
		$codex = new Codex($this->getTemporaryPath());
		$filesystem = new PHPScoperFilesystem($codex);
		$filesystem->dumpComposerFile();

		$temporaryFile = $filesystem->getOutputPath('-build-' . $filesystem->getHash()) . '/composer.json';

		self::$filesystem->dumpFile($temporaryFile, '{ "name": "syntatis/howdy" }');

		$this->assertFileExists($filesystem->getBuildPath('composer.json'));
		$this->assertFileExists($temporaryFile);

		$filesystem->removeAll();

		$this->assertFileDoesNotExist($filesystem->getBuildPath('composer.json'));
		$this->assertFileDoesNotExist($temporaryFile);
	}

	/** @requires PHP >= 8.1 */
	public function testGetScoperBinPhp81000(): void
	{
		$this->dumpTemporaryFile(
			'vendor/bin/php-scoper',
			<<<'CONTENT'
			#!/usr/bin/env php
			namespace Humbug\PhpScoper;
			CONTENT,
		);

		$codex = new Codex($this->getTemporaryPath());
		$filesystem = new PHPScoperFilesystem($codex);

		$this->assertSame(
			$this->getTemporaryPath('vendor/bin/php-scoper'),
			$filesystem->getBinPath(),
		);
	}

	/** @requires PHP <= 8.0 */
	public function testGetScoperBinPhp80000(): void
	{
		$this->dumpTemporaryFile(
			'vendor/bin/php-scoper',
			<<<'CONTENT'
			#!/usr/bin/env php
			namespace Humbug\PhpScoper;
			CONTENT,
		);

		$codex = new Codex($this->getTemporaryPath());
		$filesystem = new PHPScoperFilesystem($codex);

		$this->assertSame(
			$this->getTemporaryPath('vendor/bin/php-scoper-0.17.5'),
			$filesystem->getBinPath(),
		);
	}

	public function testDumpComposerWithEmptyInstallDev(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
			json_encode(
				[
					'name' => 'syntatis/howdy',
					'require' => ['php' => '>=7.4'],
					'require-dev' => ['symfony/var-dumper' => '^5.3'],
					'extra' => [
						'codex' => [
							'scoper' => ['install-dev' => []],
						],
					],
				],
			),
		);

		$filesystem = new PHPScoperFilesystem(new Codex($this->getTemporaryPath()));
		$filesystem->dumpComposerFile();

		$content = json_decode(file_get_contents($filesystem->getBuildPath('composer.json')), true);

		$this->assertFalse(isset($content['extra']['codex']['scoper']['install-dev']));
	}

	/**
	 * @dataProvider dataDumpComposerWithInvalidInstallDev
	 *
	 * @param mixed $data Invalid data can be anything.
	 */
	public function testDumpComposerWithInvalidInstallDev($data): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
			json_encode(
				[
					'name' => 'syntatis/howdy',
					'require' => ['php' => '>=7.4'],
					'require-dev' => ['symfony/var-dumper' => '^5.3'],
					'extra' => [
						'codex' => [
							'scoper' => ['install-dev' => $data],
						],
					],
				],
			),
		);

		$this->expectException(InvalidOptionsException::class);

		new PHPScoperFilesystem(new Codex($this->getTemporaryPath()));
	}

	public function dataDumpComposerWithInvalidInstallDev(): iterable
	{
		yield 'as string' => ['symfony/var-dumper'];
		yield 'as number' => [1];
		yield 'as null' => [null];
	}
}
