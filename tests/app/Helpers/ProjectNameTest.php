<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin\Tests\Helpers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Syntatis\ComposerProjectPlugin\Helpers\ProjectName;

use function sprintf;

class ProjectNameTest extends TestCase
{
	/** @dataProvider dataValidName */
	public function testValidName(string $name, string $expect): void
	{
		self::assertSame($expect, (string) (new ProjectName($name)));
	}

	public static function dataValidName(): iterable
	{
		yield ['foo/bar', 'foo/bar'];
		yield ['foo/Bar_one', 'foo/bar_one']; // Invalid. But, normalized to lowercase.
		yield ['Foo/bar_one', 'foo/bar_one']; // Invalid. But, normalized to lowercase.
	}

	/** @dataProvider dataEmptyName */
	public function testEmptyName(string $name): void
	{
		self::expectException(InvalidArgumentException::class);
		self::expectExceptionMessage('[syntatis] The project name cannnot be blank.');

		new ProjectName($name, '[syntatis]');
	}

	public static function dataEmptyName(): iterable
	{
		yield [''];
		yield [' '];
	}

	/** @dataProvider dataInvalidName */
	public function testInvalidName(string $name): void
	{
		self::expectException(InvalidArgumentException::class);
		self::expectExceptionMessage(sprintf('[syntatis] The project name must follow the format "vendor/package", e.g. "acme/plugin-name". "%s" given.', $name));

		new ProjectName($name, '[syntatis]');
	}

	public static function dataInvalidName(): iterable
	{
		yield ['foo'];
		yield ['foo_bar'];
		yield ['foo.bar'];
		yield ['foo bar'];
		yield ['foo-bar'];
		yield ['foo/bar one'];
		yield ['@foo/bar'];
	}

	/** @dataProvider dataGetVendorName */
	public function testGetVendorName(string $name, string $expect): void
	{
		self::assertSame($expect, (new ProjectName($name))->getVendorName());
	}

	public static function dataGetVendorName(): iterable
	{
		yield ['foo/foo_bar', 'foo'];
		yield ['foo-bar/bar', 'foo-bar'];
		yield ['foo_bar/foo-bar', 'foo_bar'];
	}

	/** @dataProvider dataGetPackageName */
	public function testGetPackageName(string $name, string $expect): void
	{
		self::assertSame($expect, (new ProjectName($name))->getPackageName());
	}

	public static function dataGetPackageName(): iterable
	{
		yield ['foo/bar', 'bar'];
		yield ['foo/foo-bar', 'foo-bar'];
		yield ['foo/foo_bar', 'foo_bar'];
	}

	public function testTooLong(): void
	{
		self::expectException(InvalidArgumentException::class);
		self::expectExceptionMessage('[syntatis] The project name must be less than or equal to 214 characters.');

		new ProjectName(
			'vendor/lorem-ipsum-dolor-sit-amet-consectetur-adipiscing-elit-sed-non-tortor-ullamcorper-faucibus-velit-quis-cursus-mauris-etiam-auctor-accumsan-arcu-nulla-ullamcorper-fermentum-laoreet-vestibulum-vehicula-scelerisque-sagittis-donec-volutpat-dolor-nec-commodo-blandit',
			'[syntatis]',
		);
	}

	public function testToNamesapce(): void
	{
		self::assertSame(
			'Vendor\SubPackage',
			(new ProjectName('vendor/sub-package'))->toNamespace(),
		);
	}
}
