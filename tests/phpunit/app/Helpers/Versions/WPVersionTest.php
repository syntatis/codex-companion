<?php

declare(strict_types=1);

namespace Syntatis\Tests\Helpers\Versions;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Syntatis\Codex\Companion\Helpers\Versions\WPVersion;

class WPVersionTest extends TestCase
{
	/** @dataProvider dataStringVersion */
	public function testStringVersion(string $value, string $expect): void
	{
		$this->assertSame($expect, (string) (new WPVersion($value)));
	}

	public static function dataStringVersion(): iterable
	{
		yield ['0.0.0', '0.0.0'];
		yield ['0.0', '0.0.0'];
		yield ['1.0.0', '1.0.0'];
		yield ['1.0', '1.0.0'];
		yield ['v1.0.0', '1.0.0'];
		yield ['v1.0', '1.0.0'];
	}

	/** @dataProvider dataStringVersionInvalid */
	public function testStringVersionInvalid(string $value): void
	{
		$this->expectException(InvalidArgumentException::class);

		new WPVersion($value);
	}

	public static function dataStringVersionInvalid(): iterable
	{
		yield ['0'];
		yield [''];
		yield ['0.'];
	}

	/** @dataProvider dataIncrementMajor */
	public function testIncrementMajor(string $value, string $expect): void
	{
		$instance = new WPVersion($value);
		$version = $instance->incrementMajor();

		$this->assertSame($expect, (string) $version);
	}

	public static function dataIncrementMajor(): iterable
	{
		yield ['0.0.0', '1.0.0'];
		yield ['0.0', '1.0.0'];
		yield ['1.0.0', '2.0.0'];
		yield ['1.0', '2.0.0'];
		yield ['v1.0.0', '2.0.0'];
		yield ['v1.0', '2.0.0'];
	}

	/** @dataProvider dataIncrementMinor */
	public function testIncrementMinor(string $value, string $expect): void
	{
		$instance = new WPVersion($value);
		$version = $instance->incrementMinor();

		$this->assertSame($expect, (string) $version);
	}

	public static function dataIncrementMinor(): iterable
	{
		yield ['0.0.0', '0.1.0'];
		yield ['0.0', '0.1.0'];
		yield ['1.0.0', '1.1.0'];
		yield ['1.0', '1.1.0'];
		yield ['1.1.0', '1.2.0'];
		yield ['1.1', '1.2.0'];
		yield ['v1.0.0', '1.1.0'];
		yield ['v1.0', '1.1.0'];
		yield ['v1.1.0', '1.2.0'];
		yield ['v1.1', '1.2.0'];
	}

	/** @dataProvider dataIncrementPatch */
	public function testIncrementPatch(string $value, string $expect): void
	{
		$instance = new WPVersion($value);
		$version = $instance->incrementPatch();

		$this->assertSame($expect, (string) $version);
	}

	public static function dataIncrementPatch(): iterable
	{
		yield ['0.0.0', '0.0.1'];
		yield ['0.0', '0.0.1'];
		yield ['1.0.0', '1.0.1'];
		yield ['1.0', '1.0.1'];
		yield ['1.1.0', '1.1.1'];
		yield ['1.1', '1.1.1'];
		yield ['1.1.1', '1.1.2'];

		// With v* prefix
		yield ['v1.0.0', '1.0.1'];
		yield ['v1.0', '1.0.1'];
		yield ['v1.1.0', '1.1.1'];
		yield ['v1.1', '1.1.1'];
		yield ['v1.1.1', '1.1.2'];
	}
}
