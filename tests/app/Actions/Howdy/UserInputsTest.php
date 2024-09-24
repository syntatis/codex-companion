<?php

declare(strict_types=1);

namespace Codex\Companion\Tests\Actions\Howdy;

use Codex\Companion\Actions\Initializers\Howdy\UserInputs;
use Composer\IO\ConsoleIO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserInputsTest extends TestCase
{
	public function testPromptWithDefaultAnswers(): void
	{
		/** @var ConsoleIO&MockObject $ioMock */
		$ioMock = $this->getMockBuilder('Composer\IO\ConsoleIO')
			->disableOriginalConstructor()
			->getMock();
		$ioMock
			->expects(self::exactly(4))
			->method('askAndValidate')
			->will(self::returnCallback(static function ($param, $validate, $retries, $default) {
				switch ($param) {
					case '[acme] Plugin slug: ':
						return $validate('acme-awesome-plugin');

					case '[acme] Plugin name (optional) [<comment>' . $default . '</comment>]: ':
						return $validate($default);

					case '[acme] PHP namespace (optional) [<comment>' . $default . '</comment>]: ':
						return $validate($default);

					case '[acme] Vendor prefix (optional) [<comment>' . $default . '</comment>]: ':
						return $validate($default);
				}
			}));

		self::assertSame([
			'vendor_prefix' => 'AcmeAwesomePlugin\Vendor',
			'php_namespace' => 'AcmeAwesomePlugin',
			'wp_plugin_name' => 'Acme Awesome Plugin',
			'wp_plugin_slug' => 'acme-awesome-plugin',
		], (new UserInputs($ioMock, '[acme]'))->get());
	}

	public function testPromptWithCustomAnswers(): void
	{
		/** @var ConsoleIO&MockObject $ioMock */
		$ioMock = $this->getMockBuilder('Composer\IO\ConsoleIO')
			->disableOriginalConstructor()
			->getMock();
		$ioMock
			->expects(self::exactly(4))
			->method('askAndValidate')
			->will(self::returnCallback(static function ($param, $validate, $retries, $default) {
				switch ($param) {
					case '[acme] Plugin slug: ':
						return $validate('awesome-plugin');

					case '[acme] Plugin name (optional) [<comment>' . $default . '</comment>]: ':
						return $validate('Awesome Plugin');

					case '[acme] PHP namespace (optional) [<comment>' . $default . '</comment>]: ':
						return $validate('AwesomePlugin');

					case '[acme] Vendor prefix (optional) [<comment>' . $default . '</comment>]: ':
						return $validate('SV');
				}
			}));

		self::assertSame([
			'vendor_prefix' => 'SV',
			'php_namespace' => 'AwesomePlugin',
			'wp_plugin_name' => 'Awesome Plugin',
			'wp_plugin_slug' => 'awesome-plugin',
		], (new UserInputs($ioMock, '[acme]'))->get());
	}
}
