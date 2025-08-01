<?php

declare(strict_types=1);

namespace Syntatis\Tests\Projects\Howdy\InitializeFiles;

use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles\CommonFiles;
use Syntatis\Tests\WithTemporaryFiles;

class CommonFilesTest extends TestCase
{
	use WithTemporaryFiles;

	/** @dataProvider dataPHPNamespacePattern */
	public function testPHPNamespacePattern(string $content, string $expect): void
	{
		$this->dumpTemporaryFile('file.php', $content);
		$instance = new CommonFiles(
			[
				'php_vendor_prefix' => 'PluginName\Vendor',
				'php_namespace' => 'PluginName',
				'wp_plugin_name' => 'Plugin Name',
				'wp_plugin_slug' => 'plugin-name',
				'wp_plugin_description' => 'This is a plugin description.',
				'camelcase' => 'pluginName',
				'kebabcase' => 'plugin-name',
				'snakecase' => 'plugin_name',
			],
			[
				'php_vendor_prefix' => 'Acme\Plugin\Vendor',
				'php_namespace' => 'Acme\Plugin',
				'wp_plugin_name' => 'Acme Plugin',
				'wp_plugin_slug' => 'acme-plugin',
				'wp_plugin_description' => 'This is a new plugin description.',
				'camelcase' => 'acmePlugin',
				'kebabcase' => 'acme-plugin',
				'snakecase' => 'acme_plugin',
			],
		);
		$instance->setFile(new SplFileInfo($this->getTemporaryPath('file.php')));
		$instance->dump();

		$this->assertStringEqualsFile($this->getTemporaryPath('file.php'), $expect);
	}

	public static function dataPHPNamespacePattern(): iterable
	{
		yield [
			'PluginName;',
			'Acme\Plugin;',
		];

		yield [
			'"PluginName"',
			'"Acme\Plugin"',
		];

		yield [
			'\'PluginName\'',
			'\'Acme\Plugin\'',
		];

		yield [
			'"PluginName\Foo"',
			'"Acme\Plugin\Foo"',
		];

		yield [
			'\'PluginName\Foo\'',
			'\'Acme\Plugin\Foo\'',
		];

		yield [
			'PluginName\Foo;',
			'Acme\Plugin\Foo;',
		];

		yield [
			'PluginName\foo_func;',
			'Acme\Plugin\foo_func;',
		];

		yield [
			'<?php namespace PluginName;',
			'<?php namespace Acme\Plugin;',
		];

		yield [
			'<?php namespace PluginName\Foo;',
			'<?php namespace Acme\Plugin\Foo;',
		];

		yield [
			'<?php use PluginName\foo_func;',
			'<?php use Acme\Plugin\foo_func;',
		];

		yield [
			'\PluginName\Foo::class',
			'\Acme\Plugin\Foo::class',
		];

		yield [
			'return [\PluginName\Foo::class,]',
			'return [\Acme\Plugin\Foo::class,]',
		];

		yield [
			'class_exists(\PluginName\Foo::class)',
			'class_exists(\Acme\Plugin\Foo::class)',
		];

		yield [
			'class_exists("\PluginName")',
			'class_exists("\Acme\Plugin")',
		];

		yield [
			'class_exists("\PluginName\Foo")',
			'class_exists("\Acme\Plugin\Foo")',
		];

		yield [
			"class_exists('\PluginName')",
			"class_exists('\Acme\Plugin')",
		];

		yield [
			"class_exists('\PluginName\Foo')",
			"class_exists('\Acme\Plugin\Foo')",
		];

		yield [
			'A\PluginName\Bar',
			'A\PluginName\Bar',
		];

		yield [
			'A\Bar\PluginName;',
			'A\Bar\PluginName;',
		];

		yield [
			'APluginName\Bar',
			'APluginName\Bar',
		];
	}
}
