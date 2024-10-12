<?php

declare(strict_types=1);

namespace Syntatis\Tests\Helpers;

use PHPUnit\Framework\TestCase;
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

	private Codex $codex;

	public function setUp(): void
	{
		parent::setUp();

		self::setUpTemporaryPath();
		self::createTemporaryFile(
			'/composer.json',
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

		$this->codex = new Codex(self::getTemporaryPath());
	}

	public function tearDown(): void
	{
		self::tearDownTemporaryPath();

		parent::tearDown();
	}

	public function testGetHash(): void
	{
		$this->assertMatchesRegularExpression(
			'/^[a-fA-F0-9]{32}$/',
			(new PHPScoperFilesystem($this->codex))->getHash(),
		);
	}

	public function testGetOutputPath(): void
	{
		$this->assertSame(
			self::getTemporaryPath('/dist/autoload'),
			(new PHPScoperFilesystem($this->codex))->getOutputPath(),
		);

		$this->assertSame(
			self::getTemporaryPath('/dist/autoload/foo'),
			(new PHPScoperFilesystem($this->codex))->getOutputPath('/foo'),
		);

		self::createTemporaryFile('/composer.json', json_encode([
			'name' => 'syntatis/howdy',
			'require' => ['php' => '>=7.4'],
			'extra' => [
				'codex' => [
					'scoper' => ['output-dir' => 'foo-autoload'],
				],
			],
		], JSON_UNESCAPED_SLASHES));

		$this->assertSame(
			self::getTemporaryPath('/foo-autoload'),
			(new PHPScoperFilesystem(new Codex(self::getTemporaryPath())))->getOutputPath(),
		);
	}

	public function testGetTemporaryPath(): void
	{
		$filesystem = new PHPScoperFilesystem($this->codex);

		$this->assertSame(
			self::getTemporaryPath('/dist/autoload-build-' . $filesystem->getHash()),
			$filesystem->getBuildPath(),
		);

		$this->assertSame(
			self::getTemporaryPath('/dist/autoload-build-' . $filesystem->getHash() . '/foo'),
			$filesystem->getBuildPath('/foo'),
		);
	}

	public function testGetScoperConfig(): void
	{
		$this->assertSame(
			self::getTemporaryPath('/scoper.inc.php'),
			(new PHPScoperFilesystem($this->codex))->getConfigPath(),
		);
	}

	public function testDumpComposer(): void
	{
		$filesystem = new PHPScoperFilesystem($this->codex);

		$this->assertFileDoesNotExist($filesystem->getBuildPath('/composer.json'));

		$filesystem->dumpComposerFile();

		$a = json_decode(file_get_contents($this->codex->getProjectPath('/composer.json')), true);
		$b = json_decode(file_get_contents($filesystem->getBuildPath('/composer.json')), true);

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

	public function testDumpComposerWithEmptyAutload(): void
	{
		self::createTemporaryFile('/composer.json', json_encode([
			'name' => 'syntatis/howdy',
			'require' => ['php' => '>=7.4'],
			'extra' => [
				'codex' => [
					'scoper' => ['output-dir' => 'foo-autoload'],
				],
			],
		], JSON_UNESCAPED_SLASHES));

		$filesystem = new PHPScoperFilesystem(new Codex(self::getTemporaryPath()));

		$this->assertFileDoesNotExist($filesystem->getBuildPath('/composer.json'));

		$filesystem->dumpComposerFile();

		$a = json_decode(file_get_contents($this->codex->getProjectPath('/composer.json')), true);
		$b = json_decode(file_get_contents($filesystem->getBuildPath('/composer.json')), true);

		$this->assertArrayNotHasKey('autoload', $a);
		$this->assertArrayNotHasKey('autoload', $b);
	}

	public function testDumpComposerInstallDev(): void
	{
		self::createTemporaryFile('/composer.json', json_encode([
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
		], JSON_UNESCAPED_SLASHES));

		$filesystem = new PHPScoperFilesystem(new Codex(self::getTemporaryPath()));
		$filesystem->dumpComposerFile();

		$a = json_decode(file_get_contents($this->codex->getProjectPath('/composer.json')), true);
		$b = json_decode(file_get_contents($filesystem->getBuildPath('/composer.json')), true);

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
		$filesystem = new PHPScoperFilesystem($this->codex);
		$filesystem->dumpComposerFile();

		$this->assertDirectoryExists($filesystem->getBuildPath());

		$filesystem->removeBuildPath();

		$this->assertDirectoryDoesNotExist($filesystem->getBuildPath());
	}

	public function testRemoveAll(): void
	{
		$filesystem = new PHPScoperFilesystem($this->codex);
		$filesystem->dumpComposerFile();

		$temporaryFile = $filesystem->getOutputPath('-build-' . $filesystem->getHash()) . '/composer.json';

		self::$filesystem->dumpFile($temporaryFile, '{ "name": "syntatis/howdy" }');

		$this->assertFileExists($filesystem->getBuildPath('/composer.json'));
		$this->assertFileExists($temporaryFile);

		$filesystem->removeAll();

		$this->assertFileDoesNotExist($filesystem->getBuildPath('/composer.json'));
		$this->assertFileDoesNotExist($temporaryFile);
	}

	public function testGetScoperBin(): void
	{
		self::createTemporaryFile(
			'/vendor/bin/php-scoper',
			<<<'CONTENT'
			#!/usr/bin/env php
			namespace Humbug\PhpScoper;
			CONTENT,
		);

		$filesystem = new PHPScoperFilesystem(new Codex(self::getTemporaryPath()));

		$this->assertSame(
			self::getTemporaryPath('/vendor/bin/php-scoper'),
			$filesystem->getBinPath(),
		);
	}

	/** @testdox should fallback to the origin path if the bin is not forwarded */
	public function testGetScoperBinNotForwarded(): void
	{
		self::createTemporaryFile(
			'/vendor-bin/php-scoper/vendor/humbug/php-scoper/bin/php-scoper',
			<<<'CONTENT'
			#!/usr/bin/env php
			namespace Humbug\PhpScoper;
			CONTENT,
		);

		$filesystem = new PHPScoperFilesystem(new Codex(self::getTemporaryPath()));

		$this->assertSame(
			self::getTemporaryPath('/vendor-bin/php-scoper/vendor/humbug/php-scoper/bin/php-scoper'),
			$filesystem->getBinPath(),
		);
	}

	/** @testdox should respects the "target-directory" configuration */
	public function testGetScoperBinCustomTargetDir(): void
	{
		self::createTemporaryFile(
			'/vendor-cli/php-scoper/vendor/humbug/php-scoper/bin/php-scoper',
			<<<'CONTENT'
			#!/usr/bin/env php
			namespace Humbug\PhpScoper;
			CONTENT,
		);

		self::createTemporaryFile(
			'/composer.json',
			json_encode(
				[
					'name' => 'syntatis/howdy',
					'require' => ['php' => '>=7.4'],
					'autoload' => [
						'psr-4' => ['Syntatis\\' => 'src/'],
					],
					'extra' => [
						'bamarni-bin' => ['target-directory' => 'vendor-cli' ],
					],
				],
				JSON_UNESCAPED_SLASHES,
			),
		);

		$filesystem = new PHPScoperFilesystem(new Codex(self::getTemporaryPath()));

		$this->assertSame(
			self::getTemporaryPath('/vendor-cli/php-scoper/vendor/humbug/php-scoper/bin/php-scoper'),
			$filesystem->getBinPath(),
		);
	}
}
