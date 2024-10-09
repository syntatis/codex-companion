<?php

declare(strict_types=1);

namespace Syntatis\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Syntatis\Codex\Companion\Helpers\PHPScoperRequirement;

class PHPScoperRequirementTest extends TestCase
{
	/** @dataProvider dataIsMet */
	public function testIsMet(string $output): void
	{
		$output = 'humbug/php-scoper v1.0.0';
		$requirement = new PHPScoperRequirement($output);

		$this->assertTrue($requirement->isMet());
	}

	public static function dataIsMet(): iterable
	{
		yield ['humbug/php-scoper v1.0.0'];
		yield ['some text before humbug/php-scoper v1.0.0 some text after'];
		yield [
			<<<'LIST'
			phpunit/phpunit
			humbug/php-scoper
			symfony/var-dumper
			LIST,
		];
	}

	/** @dataProvider dataIsNotMet */
	public function testIsNotMet(string $output): void
	{
		$requirement = new PHPScoperRequirement($output);

		$this->assertFalse($requirement->isMet());
	}

	public static function dataIsNotMet(): iterable
	{
		yield [' '];
		yield ['humbug/php-scopers'];
		yield ['some text before symfony/php-scoper v1.0.1 some text after'];
		yield [
			<<<'LIST'
			phpunit/phpunit
			symfony/var-dumper
			LIST,
		];
	}
}
