<?php

declare(strict_types=1);

namespace Syntatis\Tests\Helpers\Versions;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Syntatis\Codex\Companion\Helpers\Versions\WPPluginRequiresPHP;

class WPPluginRequiresPHPTest extends TestCase
{
	/** @dataProvider dataStringVersion */
	public function testStringVersion(string $value, string $expect): void
	{
		$this->assertSame($expect, (string) (new WPPluginRequiresPHP($value)));
	}

	public static function dataStringVersion(): iterable
	{
		yield ['0.0.0', '0.0'];
		yield ['0.0', '0.0'];
		yield ['1.0.0', '1.0'];
		yield ['1.1.2', '1.1'];
		yield ['v1.0.0', '1.0'];
		yield ['v1.0', '1.0'];
		yield ['v1.1.2', '1.1'];
	}

	/** @dataProvider dataStringVersionInvalid */
	public function testStringVersionInvalid(string $value): void
	{
		$this->expectException(InvalidArgumentException::class);

		new WPPluginRequiresPHP($value);
	}

	public static function dataStringVersionInvalid(): iterable
	{
		yield ['0'];
		yield [''];
		yield ['0.'];
	}
}
