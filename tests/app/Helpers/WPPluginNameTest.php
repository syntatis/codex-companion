<?php

declare(strict_types=1);

namespace Syntatis\Tests\Helpers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Syntatis\Codex\Companion\Helpers\WPPluginName;

use function str_repeat;

class WPPluginNameTest extends TestCase
{
	/** @dataProvider dataValidName */
	public function testValidName(string $name, string $expect): void
	{
		self::assertSame($expect, (string) (new WPPluginName($name, '[syntatis]')));
	}

	public static function dataValidName(): iterable
	{
		yield ['My Awesome Plugin', 'My Awesome Plugin'];
		yield ['my awesome plugin', 'my awesome plugin'];
	}

	/** @dataProvider dataEmptyName */
	public function testEmptyName(string $name): void
	{
		self::expectException(InvalidArgumentException::class);
		self::expectExceptionMessage('[syntatis] The plugin name cannnot be blank.');

		new WPPluginName($name, '[syntatis]');
	}

	public static function dataEmptyName(): iterable
	{
		yield [''];
		yield [' '];
	}

	public function testTooLong(): void
	{
		self::expectException(InvalidArgumentException::class);
		self::expectExceptionMessage('[syntatis] The plugin name must be less than or equal to 214 characters.');

		new WPPluginName(str_repeat('a', 215), '[syntatis]');
	}
}
