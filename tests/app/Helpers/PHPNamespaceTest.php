<?php

declare(strict_types=1);

namespace Codex\Companion\Tests\Helpers;

use Codex\Companion\Helpers\PHPNamespace;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PHPNamespaceTest extends TestCase
{
	/** @dataProvider dataValidNamespace */
	public function testValidNamespace(string $name): void
	{
		self::assertSame($name, (string) (new PHPNamespace($name, '[syntatis]')));
	}

	public static function dataValidNamespace(): iterable
	{
		yield ['VendorName\PackageName\SubPackage'];
		yield ['Vendor_Name\Package_Name'];
		yield ['VendorName\PackageName\SubPackage123'];
		yield ['VendorName123\PackageName'];
	}

	/** @dataProvider dataEmptyName */
	public function testBlankName(string $name): void
	{
		self::expectException(InvalidArgumentException::class);
		self::expectExceptionMessage('[syntatis] The PHP namespace cannot be blank.');

		new PHPNamespace($name, '[syntatis]');
	}

	public static function dataEmptyName(): iterable
	{
		yield [''];
		yield [' '];
	}

	/** @dataProvider dataInvalidNamespace */
	public function testInvalidNamespace(string $name): void
	{
		self::expectException(InvalidArgumentException::class);
		self::expectExceptionMessage('[syntatis] Invalid namespace format.');

		new PHPNamespace($name, '[syntatis]');
	}

	public static function dataInvalidNamespace(): iterable
	{
		yield ['vendorname\\PackageName'];
		yield ['VendorName\\packagename'];
		yield ['VendorName\\package-name'];
	}

	public function testTooLong(): void
	{
		self::expectException(InvalidArgumentException::class);
		self::expectExceptionMessage('[syntatis] The PHP namespace must be less than or equal to 214 characters.');

		new PHPNamespace(
			'VendorName\PackageName\LoremIpsumDolorSitAmetConsecteturAdipiscingElitSedNonTortorUllamcorperFaucibusVelitQuisCursusMaurisEtiamAuctorAccumsanArcuNullaUllamcorperFermentumLaoreetVestibulumVehiculaScelerisqueSagittisDonecVolutpatDolorNecCommodoBlandit',
			'[syntatis]',
		);
	}
}
