<?php

declare(strict_types=1);

namespace Syntatis\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Syntatis\Codex\Companion\Helpers\WPPluginDescription;

class WPPluginDescriptionTest extends TestCase
{
	/** @dataProvider dataValidDescription */
	public function testValidName(string $input, string $expect): void
	{
		$this->assertSame($expect, (string) (new WPPluginDescription($input)));
	}

	public static function dataValidDescription(): iterable
	{
		yield ['This is a plugin description.', 'This is a plugin description.'];
	}
}
