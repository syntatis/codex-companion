<?php

declare(strict_types=1);

namespace Syntatis\Tests\Clients;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Syntatis\Codex\Companion\Clients\PHPScoperInc;
use Syntatis\Tests\WithTemporaryFiles;

class PHPScoperIncTest extends TestCase
{
	use WithTemporaryFiles;

	public function setUp(): void
	{
		parent::setUp();

		self::setUpTemporaryPath();
		self::createTemporaryFile(
			'/composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": "app/"
					}
				}
			}
			CONTENT,
		);
	}

	public function tearDown(): void
	{
		self::tearDownTemporaryPath();

		parent::tearDown();
	}

	public function testExposeGlobals(): void
	{
		$instance = new PHPScoperInc(self::getTemporaryPath());

		$this->assertTrue($instance->get()['expose-global-constants']);
		$this->assertTrue($instance->get()['expose-global-classes']);
		$this->assertTrue($instance->get()['expose-global-functions']);
	}

	public function testOverrideExposeGlobals(): void
	{
		$instance = new PHPScoperInc(self::getTemporaryPath(), [
			'expose-global-constants' => false,
			'expose-global-classes' => false,
		]);

		$this->assertFalse($instance->get()['expose-global-constants']);
		$this->assertFalse($instance->get()['expose-global-classes']);
		$this->assertTrue($instance->get()['expose-global-functions']);
	}

	public function testPrefixNotSet(): void
	{
		self::createTemporaryFile(
			'/composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\Vendor\\": "app/"
					}
				}
			}
			CONTENT,
		);

		$instance = new PHPScoperInc(self::getTemporaryPath());

		$this->assertNull($instance->get()['prefix']);
	}

	public function testPrefix(): void
	{
		self::createTemporaryFile(
			'/composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\Vendor\\": "app/"
					}
				},
				"extra": {
					"codex": {
						"scoper": {
							"prefix": "PVA\\Vendor"
						}
					}
				}
			}
			CONTENT,
		);
		$instance = new PHPScoperInc(self::getTemporaryPath());

		$this->assertSame('PVA\\Vendor', $instance->get()['prefix']);
	}

	public function testOverridePrefix(): void
	{
		self::createTemporaryFile(
			'/composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": "app/"
					}
				},
				"extra": {
					"codex": {
						"scoper": {
							"prefix": "PVA\\Vendor"
						}
					}
				}
			}
			CONTENT,
		);
		$instance = new PHPScoperInc(self::getTemporaryPath(), ['prefix' => 'FOO\\Bar']);

		$this->assertSame('PVA\\Vendor', $instance->get()['prefix']);
	}

	public function testExcludeNamespaces(): void
	{
		self::createTemporaryFile(
			'/composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": "app/"
					}
				},
				"extra": {
					"codex": {
						"scoper": {
							"prefix": "PVA\\Vendor"
						}
					}
				}
			}
			CONTENT,
		);
		$instance = new PHPScoperInc(self::getTemporaryPath());

		$this->assertContains('PluginName', $instance->get()['exclude-namespaces']);
	}

	public function testAdditionalExcludeNamespaces(): void
	{
		self::createTemporaryFile(
			'/composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": ["app/", "src/"]
					}
				},
				"extra": {
					"codex": {
						"scoper": {
							"prefix": "PVA\\Vendor",
							"exclude-namespaces": ["Whoops", "Symfony\\Component\\Console\\"]
						}
					}
				}
			}
			CONTENT,
		);
		$instance = new PHPScoperInc(self::getTemporaryPath());

		$this->assertContains('PluginName', $instance->get()['exclude-namespaces']);
		$this->assertContains('Whoops', $instance->get()['exclude-namespaces']);
		$this->assertContains('Symfony\\Component\\Console', $instance->get()['exclude-namespaces']);
	}

	public function testExcludeFiles(): void
	{
		self::createTemporaryFile(
			'/composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": ["app/"]
					}
				}
			}
			CONTENT,
		);
		self::createTemporaryFile('/vendor/foo.css', '');
		self::createTemporaryFile('/vendor/foo.html', '');
		self::createTemporaryFile('/vendor/foo.js', '');
		self::createTemporaryFile('/vendor/foo.html.php', '');

		$instance = new PHPScoperInc(self::getTemporaryPath());
		$instance = $instance->excludeFiles([
			self::getTemporaryPath('/vendor/foo.css'),
			self::getTemporaryPath('/vendor/foo.html'),
			self::getTemporaryPath('/vendor/foo.js'),
			self::getTemporaryPath('/vendor/foo.html.php'),
		]);

		$this->assertContains(self::getTemporaryPath('/vendor/foo.css'), $instance->get()['exclude-files']);
		$this->assertContains(self::getTemporaryPath('/vendor/foo.html'), $instance->get()['exclude-files']);
		$this->assertContains(self::getTemporaryPath('/vendor/foo.js'), $instance->get()['exclude-files']);
		$this->assertContains(self::getTemporaryPath('/vendor/foo.html.php'), $instance->get()['exclude-files']);
	}

	public function testAddFinder(): void
	{
		self::createTemporaryFile(
			'/composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": ["app/"]
					}
				}
			}
			CONTENT,
		);

		$instance = new PHPScoperInc(self::getTemporaryPath());

		$finder = Finder::create();
		$instance = $instance->addFinder($finder);

		$this->assertContains($finder, $instance->get()['finders']);
	}

	public function testAddPatcher(): void
	{
		self::createTemporaryFile(
			'/composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": ["app/"]
					}
				}
			}
			CONTENT,
		);

		$patcher = static function () {
			return 'patched';
		};
		$instance = new PHPScoperInc(self::getTemporaryPath());
		$instance = $instance->addPatcher($patcher);

		$this->assertContains($patcher, $instance->get()['patchers']);
	}
}
