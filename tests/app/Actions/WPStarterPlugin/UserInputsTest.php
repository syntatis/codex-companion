<?php

declare(strict_types=1);

namespace Syntatis\ComposerProjectPlugin\Tests\Actions\WPStarterPlugin;

use Composer\IO\ConsoleIO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Syntatis\ComposerProjectPlugin\Actions\Initializers\WPStarterPlugin\UserInputs;

class UserInputsTest extends TestCase
{
	public function testPromptWithDefaultAnswers(): void
	{
		/** @var ConsoleIO&MockObject $ioMock */
		$ioMock = $this->getMockBuilder('Composer\IO\ConsoleIO')
			->disableOriginalConstructor()
			->getMock();
		$ioMock
			->expects(self::exactly(5))
			->method('askAndValidate')
			->will(self::returnCallback(static function ($param, $validate, $retries, $default) {
				switch ($param) {
					case '[acme] Project name: ':
						return $validate('acme/awesome-plugin');

					case '[acme] Plugin slug (optional) [<comment>' . $default . '</comment>]: ':
						return $validate($default);

					case '[acme] Plugin name (optional) [<comment>' . $default . '</comment>]: ':
						return $validate($default);

					case '[acme] PHP namespace (optional) [<comment>' . $default . '</comment>]: ':
						return $validate($default);

					case '[acme] Vendor prefix (optional) [<comment>' . $default . '</comment>]: ':
						return $validate($default);
				}
			}));

		self::assertSame([
			'php_namespace' => 'Acme\AwesomePlugin',
			'vendor_prefix' => 'Acme\AwesomePlugin\Vendor',
			'project_name' => 'acme/awesome-plugin',
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
			->expects(self::exactly(5))
			->method('askAndValidate')
			->will(self::returnCallback(static function ($param, $validate, $retries, $default) {
				switch ($param) {
					case '[acme] Project name: ':
						return $validate('acme/awesome-plugin');

					case '[acme] Plugin slug (optional) [<comment>' . $default . '</comment>]: ':
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
			'php_namespace' => 'AwesomePlugin',
			'vendor_prefix' => 'SV',
			'project_name' => 'acme/awesome-plugin',
			'wp_plugin_name' => 'Awesome Plugin',
			'wp_plugin_slug' => 'awesome-plugin',
		], (new UserInputs($ioMock, '[acme]'))->get());
	}
}
