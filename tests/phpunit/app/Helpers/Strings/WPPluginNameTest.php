<?php

declare(strict_types=1);

namespace Syntatis\Tests\Helpers\Strings;

use PHPUnit\Framework\TestCase;
use Syntatis\Codex\Companion\Helpers\Strings\WPPluginName;

class WPPluginNameTest extends TestCase
{
	/** @dataProvider dataValidName */
	public function testValidName(string $name, string $expect): void
	{
		$this->assertSame($expect, (string) (new WPPluginName($name)));
	}

	public static function dataValidName(): iterable
	{
		yield ['My Awesome Plugin', 'My Awesome Plugin'];
		yield ['my awesome plugin', 'my awesome plugin'];
	}
}
