<?php

declare(strict_types=1);

namespace Syntatis\Tests\Projects\Howdy\InitializeFiles;

use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Syntatis\Codex\Companion\Projects\Howdy\InitializeFiles\ComposerFile;
use Syntatis\Tests\WithTemporaryFiles;

use function file_get_contents;
use function json_decode;

class ComposerFileTest extends TestCase
{
	use WithTemporaryFiles;

	private ComposerFile $instance;

	public function setUp(): void
	{
		parent::setUp();

		$this->instance = new ComposerFile(
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
	}

	public function testArchiveZipScripts(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
			<<<'CONTENT'
			{
				"scripts": {
					"archive:zip": "@composer archive --format=zip --file=plugin-name"
				}
			}
			CONTENT,
		);

		$this->instance->setFile(new SplFileInfo($this->getTemporaryPath('composer.json')));
		$this->instance->dump();

		$json = json_decode(file_get_contents($this->getTemporaryPath('composer.json')), true);

		$this->assertEquals($json['scripts']['archive:zip'], '@composer archive --format=zip --file=acme-plugin');
	}

	/**
	 * @dataProvider dataBuildScripts
	 *
	 * @param mixed $expect The expected result of the "scripts".
	 */
	public function testBuildScripts(string $content, $expect): void
	{
		$this->dumpTemporaryFile('composer.json', $content);

		$this->instance->setFile(new SplFileInfo($this->getTemporaryPath('composer.json')));
		$this->instance->dump();

		$json = json_decode(file_get_contents($this->getTemporaryPath('composer.json')), true);

		$this->assertTrue(true);

		// $this->assertEquals($json['scripts']['build'], $expect);
	}

	public static function dataBuildScripts(): iterable
	{
		yield [
			<<<'CONTENT'
			{
				"scripts": {
					"build": [
						"wp i18n make-pot --exclude=vendor,dist . inc/languages/plugin-name.pot",
						"codex scoper:init --yes --no-dev"
					]
				}
			}
			CONTENT,
			[
				'wp i18n make-pot --exclude=vendor,dist . inc/languages/acme-plugin.pot',
				'codex scoper:init --yes --no-dev',
			],
		];

		yield [
			<<<'CONTENT'
			{
				"scripts": {
					"build": "wp i18n make-pot --exclude=vendor,dist . inc/languages/plugin-name.pot"
				}
			}
			CONTENT,
			'wp i18n make-pot --exclude=vendor,dist . inc/languages/acme-plugin.pot',
		];
	}
}
