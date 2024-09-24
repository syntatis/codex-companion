<?php

declare(strict_types=1);

namespace Syntatis\Tests\Helpers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Syntatis\Codex\Companion\Helpers\WPPluginSlug;

use function str_repeat;

class WPPluginSlugTest extends TestCase
{
	/** @dataProvider dataValidName */
	public function testValidName(string $name, string $expect): void
	{
		self::assertSame($expect, (string) (new WPPluginSlug($name, '[syntatis]')));
	}

	public static function dataValidName(): iterable
	{
		yield ['foo/bar', 'foo-bar'];
		yield ['foo\bar', 'foo-bar'];
		yield ['foo.bar', 'foo-bar'];
		yield ['foo bar', 'foo-bar'];
		yield ['FooBar', 'foo-bar'];
	}

	/** @dataProvider dataEmptyName */
	public function testEmptyName(string $name): void
	{
		self::expectException(InvalidArgumentException::class);
		self::expectExceptionMessage('[syntatis] The plugin slug cannnot be blank.');

		new WPPluginSlug($name, '[syntatis]');
	}

	public static function dataEmptyName(): iterable
	{
		yield [''];
		yield [' '];
	}

	public function testTooLong(): void
	{
		self::expectException(InvalidArgumentException::class);
		self::expectExceptionMessage('[syntatis] The plugin slug must be less than or equal to 214 characters.');

		new WPPluginSlug(str_repeat('a', 215), '[syntatis]');
	}

	public function testToPluginName(): void
	{
		self::assertSame(
			'My Awesome Plugin',
			(new WPPluginSlug('my-awesome-plugin', '[syntatis]'))->toPluginName(),
		);
	}
}
