<?php

declare(strict_types=1);

namespace Syntatis\Tests\Console\ProjectInitCommand\Howdy;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Syntatis\Codex\Companion\Console\ProjectInitCommand\Howdy\UserInputPrompts;
use Syntatis\Codex\Companion\Exceptions\MissingRequiredInfo;
use Syntatis\Tests\WithTemporaryFiles;

use function str_repeat;

class UserInputPromptsTest extends TestCase
{
	use WithTemporaryFiles;

	/**
	 * When the required props are not available, either because the files are
	 * missing, or the string required cannot be found or parsed within the
	 * file, it should throw an error and show the names of the missing
	 * props.
	 *
	 * @testdox should throw an exception when all the required data is missing
	 */
	public function testWithoutAllProps(): void
	{
		/** @var StyleInterface&MockObject $style */
		$style = $this->getMockBuilder(SymfonyStyle::class)
			->disableOriginalConstructor()
			->getMock();

		$this->expectException(MissingRequiredInfo::class);
		$this->expectExceptionMessageMatches('/php_vendor_prefix\, php_namespace\, wp_plugin_name\, wp_plugin_slug$/');

		$userInputs = new UserInputPrompts([], $style);
	}

	/** @testdox should not include the "php_vendor_prefix" in the missing prop */
	public function testWithVendorPrefixProp(): void
	{
		/** @var StyleInterface&MockObject $style */
		$style = $this->getMockBuilder(SymfonyStyle::class)
			->disableOriginalConstructor()
			->getMock();

		$this->expectException(MissingRequiredInfo::class);
		$this->expectExceptionMessageMatches('/php_namespace\, wp_plugin_name\, wp_plugin_slug$/');

		new UserInputPrompts(['php_vendor_prefix' => 'Foo\Vendor'], $style);
	}

	/** @testdox should not include the "php_namespace" in the missing prop */
	public function testWithNamespace(): void
	{
		/** @var StyleInterface&MockObject $style */
		$style = $this->getMockBuilder(SymfonyStyle::class)
			->disableOriginalConstructor()
			->getMock();

		$this->expectException(MissingRequiredInfo::class);
		$this->expectExceptionMessageMatches('/wp_plugin_name\, wp_plugin_slug$/');

		new UserInputPrompts([
			'php_vendor_prefix' => 'Foo\Vendor',
			'php_namespace' => 'Foo',
		], $style);
	}

	/**
	 * In this tests, the plugin file name is provided. But the file does not
	 * contain the Plugin Name information in the header, so we could only
	 * get the Plugin Slug information from the file name. For example,
	 * if the plugin file is named `plugin-name.php`, the slug would
	 * be `plugin-name`.
	 *
	 * @testdox should not include the "wp_plugin_slug" in the missing prop
	 */
	public function testWithPluginSlug(): void
	{
		/** @var StyleInterface&MockObject $style */
		$style = $this->getMockBuilder(SymfonyStyle::class)
			->disableOriginalConstructor()
			->getMock();

		$this->expectException(MissingRequiredInfo::class);
		$this->expectExceptionMessageMatches('/wp_plugin_name$/');

		new UserInputPrompts([
			'php_vendor_prefix' => 'Foo\Vendor',
			'php_namespace' => 'Foo',
			'wp_plugin_slug' => 'plugin-name',
		], $style);
	}

	/** @testdox should not throw an exception of `MissingRequiredInfo` */
	public function testWithAllProps(): void
	{
		/** @var StyleInterface&MockObject $style */
		$style = $this->getMockBuilder(SymfonyStyle::class)
			->disableOriginalConstructor()
			->getMock();

		$userInputs = new UserInputPrompts([
			'php_vendor_prefix' => 'Foo\Vendor',
			'php_namespace' => 'Foo',
			'wp_plugin_name' => 'Foo Plugin',
			'wp_plugin_slug' => 'foo-plugin',
		], $style);

		$this->assertSame(
			[
				'php_vendor_prefix' => 'Foo\Vendor',
				'php_namespace' => 'Foo',
				'wp_plugin_name' => 'Foo Plugin',
				'wp_plugin_slug' => 'foo-plugin',
			],
			$userInputs->getProjectProps(),
		);
	}

	/**
	 * As per the WordPress Handbook, the plugin Description is optional. It may
	 * not be provided. By default, `wp_plugin_description` props will not be
	 * included in the value returned from the `getProps` method.
	 *
	 * However, if the Description is included in the plugin file header, it
	 * will be included in the value returned from the `getProps` method.
	 *
	 * @testdox should not include `wp_plugin_description` in the props
	 */
	public function testWithDescription(): void
	{
		/** @var StyleInterface&MockObject $style */
		$style = $this->getMockBuilder(SymfonyStyle::class)
			->disableOriginalConstructor()
			->getMock();

		$userInputs = new UserInputPrompts([
			'php_vendor_prefix' => 'PluginName\Vendor',
			'php_namespace' => 'PluginName',
			'wp_plugin_name' => 'Plugin Name',
			'wp_plugin_slug' => 'plugin-name',
			'wp_plugin_description' => 'This is a description.',
		], $style);

		$this->assertSame(
			[
				'php_vendor_prefix' => 'PluginName\Vendor',
				'php_namespace' => 'PluginName',
				'wp_plugin_name' => 'Plugin Name',
				'wp_plugin_slug' => 'plugin-name',
				'wp_plugin_description' => 'This is a description.',
			],
			$userInputs->getProjectProps(),
		);
	}

	public function testGetInputsWithCustomSlug(): void
	{
		/** @var StyleInterface&MockObject $style */
		$style = $this->getMockBuilder(SymfonyStyle::class)
			->disableOriginalConstructor()
			->getMock();
		$style
			->method('ask')
			->will(self::returnCallback(static function ($param, $default, $callback) {
				switch ($param) {
					case 'Plugin slug':
						return $callback('acme-awesome-plugin');

					default:
						return $callback($default);
				}
			}));

		$userInputs = new UserInputPrompts([
			'php_vendor_prefix' => 'PluginName\Vendor',
			'php_namespace' => 'PluginName',
			'wp_plugin_name' => 'Plugin Name',
			'wp_plugin_slug' => 'plugin-name',
		], $style);
		$userInputs->execute($style);

		$this->assertSame(
			[
				'php_vendor_prefix' => 'AcmeAwesomePlugin\Vendor',
				'php_namespace' => 'AcmeAwesomePlugin',
				'wp_plugin_name' => 'Acme Awesome Plugin',
				'wp_plugin_slug' => 'acme-awesome-plugin',
			],
			$userInputs->getInputs(),
		);
	}

	public function testGetInputsWithDescription(): void
	{
		/** @var StyleInterface&MockObject $style */
		$style = $this->getMockBuilder(SymfonyStyle::class)
			->disableOriginalConstructor()
			->getMock();
		$style
			->method('ask')
			->will(self::returnCallback(static function ($param, $default, $callback) {
				switch ($param) {
					case 'Plugin slug':
						return $callback('acme-awesome-plugin');

					default:
						return $callback($default);
				}
			}));

		$userInputs = new UserInputPrompts([
			'php_vendor_prefix' => 'PluginName\Vendor',
			'php_namespace' => 'PluginName',
			'wp_plugin_name' => 'Plugin Name',
			'wp_plugin_slug' => 'plugin-name',
			'wp_plugin_description' => 'The plugin short description.',
		], $style);
		$userInputs->execute($style);

		$this->assertSame(
			[
				'php_vendor_prefix' => 'AcmeAwesomePlugin\Vendor',
				'php_namespace' => 'AcmeAwesomePlugin',
				'wp_plugin_name' => 'Acme Awesome Plugin',
				'wp_plugin_slug' => 'acme-awesome-plugin',
				/**
				 * Unlike the other props, the description default is not derived
				 * from the Plugin slug. The description remains as is when it
				 * is not given with custom input.
				 */
				'wp_plugin_description' => 'The plugin short description.',
			],
			$userInputs->getInputs(),
		);
	}

	/**
	 * @dataProvider dataPromptInvalidPluginSlug
	 *
	 * @param mixed $userInput
	 */
	public function testPromptInvalidPluginSlug($userInput, string $message): void
	{
		/** @var StyleInterface&MockObject $style */
		$style = $this->getMockBuilder(SymfonyStyle::class)
			->disableOriginalConstructor()
			->getMock();
		$style
			->method('ask')
			->will(self::returnCallback(static function ($param, $default, $callback) use ($userInput) {
				switch ($param) {
					/**
					 * User provides an empty string when prompted the "Plugin slug"
					 */
					case 'Plugin slug':
						return $callback($userInput);
				}
			}));

		$userInputs = new UserInputPrompts([
			'php_vendor_prefix' => 'PluginName\Vendor',
			'php_namespace' => 'PluginName',
			'wp_plugin_name' => 'Plugin Name',
			'wp_plugin_slug' => 'plugin-name',
			'wp_plugin_description' => 'The plugin short description.',
		], $style);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage($message);

		$userInputs->execute($style);
	}

	public static function dataPromptInvalidPluginSlug(): iterable
	{
		yield [null, 'The plugin slug cannnot be blank.'];
		yield [false, 'The plugin slug cannnot be blank.'];
		yield ['', 'The plugin slug cannnot be blank.'];
		yield [' ', 'The plugin slug cannnot be blank.'];
		yield [
			str_repeat('a', 215),
			'The plugin slug must be less than or equal to 214 characters.',
		];
	}

	/**
	 * @dataProvider dataPromptInvalidPluginName
	 *
	 * @param mixed $userInput
	 */
	public function testPromptInvalidPluginName($userInput, string $message): void
	{
		/** @var StyleInterface&MockObject $style */
		$style = $this->getMockBuilder(SymfonyStyle::class)
			->disableOriginalConstructor()
			->getMock();
		$style
			->method('ask')
			->will(self::returnCallback(static function ($param, $default, $callback) use ($userInput) {
				switch ($param) {
					case 'Plugin slug':
						return $callback('awesome-plugin-name');

					/**
					 * User provides an empty string when prompted the "Plugin name"
					 */
					case 'Plugin name':
						return $callback($userInput);
				}
			}));

		$userInputs = new UserInputPrompts([
			'php_vendor_prefix' => 'PluginName\Vendor',
			'php_namespace' => 'PluginName',
			'wp_plugin_name' => 'Plugin Name',
			'wp_plugin_slug' => 'plugin-name',
			'wp_plugin_description' => 'The plugin short description.',
		], $style);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage($message);

		$userInputs->execute($style);
	}

	public static function dataPromptInvalidPluginName(): iterable
	{
		yield [
			'',
			'The plugin name cannnot be blank.',
		];

		yield [
			' ',
			'The plugin name cannnot be blank.',
		];

		yield [
			null,
			'The plugin name cannnot be blank.',
		];

		yield [
			str_repeat('a', 215),
			'The plugin name must be less than or equal to 214 characters.',
		];
	}

	/**
	 * @dataProvider dataPromptInvalidNamespace
	 *
	 * @param mixed $userInput
	 */
	public function testPromptInvalidNamespace($userInput, string $message): void
	{
		/** @var StyleInterface&MockObject $style */
		$style = $this->getMockBuilder(SymfonyStyle::class)
			->disableOriginalConstructor()
			->getMock();
		$style
			->method('ask')
			->will(self::returnCallback(static function ($param, $default, $callback) use ($userInput) {
				switch ($param) {
					case 'Plugin slug':
						return $callback('awesome-plugin-name');

					case 'Plugin name':
						return $callback($default);

					/**
					 * User provides an empty string when prompted the "PHP Namespace"
					 */
					case 'PHP namespace':
						return $callback($userInput);
				}
			}));

		$userInputs = new UserInputPrompts([
			'php_vendor_prefix' => 'PluginName\Vendor',
			'php_namespace' => 'PluginName',
			'wp_plugin_name' => 'Plugin Name',
			'wp_plugin_slug' => 'plugin-name',
			'wp_plugin_description' => 'The plugin short description.',
		], $style);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage($message);

		$userInputs->execute($style);
	}

	public static function dataPromptInvalidNamespace(): iterable
	{
		yield [
			'',
			'The PHP namespace cannot be blank.',
		];

		yield [
			null,
			'The PHP namespace cannot be blank.',
		];

		yield [
			'vendorname\\PackageName',
			'Invalid PHP namespace format.',
		];

		yield [
			'VendorName\\packagename',
			'Invalid PHP namespace format.',
		];

		yield [
			'VendorName\\package-name',
			'Invalid PHP namespace format.',
		];

		yield [
			'VendorName\PackageName\LoremIpsumDolorSitAmetConsecteturAdipiscingElitSedNonTortorUllamcorperFaucibusVelitQuisCursusMaurisEtiamAuctorAccumsanArcuNullaUllamcorperFermentumLaoreetVestibulumVehiculaScelerisqueSagittisDonecVolutpatDolorNecCommodoBlandit',
			'The PHP namespace must be less than or equal to 214 characters.',
		];
	}

	/**
	 * @dataProvider dataPromptInvalidVendorPrefix
	 *
	 * @param mixed $userInput
	 */
	public function testPromptInvalidVendorPrefix($userInput, string $message): void
	{
		/** @var StyleInterface&MockObject $style */
		$style = $this->getMockBuilder(SymfonyStyle::class)
			->disableOriginalConstructor()
			->getMock();
		$style
			->method('ask')
			->will(self::returnCallback(static function ($param, $default, $callback) use ($userInput) {
				switch ($param) {
					case 'Plugin slug':
						return $callback('awesome-plugin-name');

					case 'Plugin name':
					case 'PHP namespace':
						return $callback($default);

					case 'PHP vendor prefix':
						return $callback($userInput);
				}
			}));

		$userInputs = new UserInputPrompts([
			'php_vendor_prefix' => 'PluginName\Vendor',
			'php_namespace' => 'PluginName',
			'wp_plugin_name' => 'Plugin Name',
			'wp_plugin_slug' => 'plugin-name',
			'wp_plugin_description' => 'The plugin short description.',
		], $style);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage($message);

		$userInputs->execute($style);
	}

	public static function dataPromptInvalidVendorPrefix(): iterable
	{
		yield [
			'',
			'The PHP vendor prefix cannot be blank.',
		];

		yield [
			null,
			'The PHP vendor prefix cannot be blank.',
		];

		yield [
			'vendorname\\PackageName',
			'Invalid PHP vendor prefix format.',
		];

		yield [
			'VendorName\\packagename',
			'Invalid PHP vendor prefix format.',
		];

		yield [
			'VendorName\\package-name',
			'Invalid PHP vendor prefix format.',
		];

		yield [
			'VendorName\PackageName\LoremIpsumDolorSitAmetConsecteturAdipiscingElitSedNonTortorUllamcorperFaucibusVelitQuisCursusMaurisEtiamAuctorAccumsanArcuNullaUllamcorperFermentumLaoreetVestibulumVehiculaScelerisqueSagittisDonecVolutpatDolorNecCommodoBlandit',
			'The PHP vendor prefix must be less than or equal to 214 characters.',
		];
	}

	/**
	 * @dataProvider dataPromptInvalidPluginDescription
	 *
	 * @param mixed $userInput
	 */
	public function testPromptInvalidDescription($userInput, string $message): void
	{
		/** @var StyleInterface&MockObject $style */
		$style = $this->getMockBuilder(SymfonyStyle::class)
			->disableOriginalConstructor()
			->getMock();
		$style
			->method('ask')
			->will(self::returnCallback(static function ($param, $default, $callback) use ($userInput) {
				switch ($param) {
					case 'Plugin slug':
						return $callback('awesome-plugin-name');

					case 'Plugin description':
						return $callback($userInput);

					default:
						return $callback($default);
				}
			}));

		$userInputs = new UserInputPrompts([
			'php_vendor_prefix' => 'PluginName\Vendor',
			'php_namespace' => 'PluginName',
			'wp_plugin_name' => 'Plugin Name',
			'wp_plugin_slug' => 'plugin-name',
			'wp_plugin_description' => 'The plugin short description.',
		], $style);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage($message);

		$userInputs->execute($style);
	}

	public static function dataPromptInvalidPluginDescription(): iterable
	{
		yield [
			'',
			'The plugin description cannnot be blank.',
		];

		yield [
			' ',
			'The plugin description cannnot be blank.',
		];

		yield [
			null,
			'The plugin description cannnot be blank.',
		];

		yield [
			str_repeat('a', 141),
			'The plugin description must be less than or equal to 140 characters.',
		];
	}
}
