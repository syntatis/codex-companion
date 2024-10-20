<?php

declare(strict_types=1);

namespace Syntatis\Tests\Helpers\Strings;

use PHPUnit\Framework\TestCase;
use Syntatis\Codex\Companion\Helpers\Strings\WPPluginSlug;

class WPPluginSlugTest extends TestCase
{
	/** @dataProvider dataValidName */
	public function testValidName(string $name, string $expect): void
	{
		$this->assertSame($expect, (string) (new WPPluginSlug($name)));
	}

	public static function dataValidName(): iterable
	{
		yield ['foo/bar', 'foo-bar'];
		yield ['foo\bar', 'foo-bar'];
		yield ['foo.bar', 'foo-bar'];
		yield ['foo bar', 'foo-bar'];
		yield ['FooBar', 'foo-bar'];
	}
}
