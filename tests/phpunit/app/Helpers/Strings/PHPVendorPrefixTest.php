<?php

declare(strict_types=1);

namespace Syntatis\Tests\Helpers\Strings;

use PHPUnit\Framework\TestCase;
use Syntatis\Codex\Companion\Helpers\Strings\PHPVendorPrefix;

class PHPVendorPrefixTest extends TestCase
{
	/** @dataProvider dataValidNamespace */
	public function testValidNamespace(string $name): void
	{
		$this->assertSame($name, (string) (new PHPVendorPrefix($name)));
	}

	public static function dataValidNamespace(): iterable
	{
		yield ['VendorName\PackageName\SubPackage'];
		yield ['Vendor_Name\Package_Name'];
		yield ['VendorName\PackageName\SubPackage123'];
		yield ['VendorName123\PackageName'];
	}
}
