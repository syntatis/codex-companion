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

		$this->setUpTemporaryPath();
		$this->dumpTemporaryFile(
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

	public function testExposeGlobals(): void
	{
		$instance = new PHPScoperInc($this->getTemporaryPath());

		$this->assertTrue($instance->get()['expose-global-constants']);
		$this->assertTrue($instance->get()['expose-global-classes']);
		$this->assertTrue($instance->get()['expose-global-functions']);
	}

	public function testOverrideExposeGlobals(): void
	{
		$instance = new PHPScoperInc($this->getTemporaryPath(), [
			'expose-global-constants' => false,
			'expose-global-classes' => false,
		]);

		$this->assertFalse($instance->get()['expose-global-constants']);
		$this->assertFalse($instance->get()['expose-global-classes']);
		$this->assertTrue($instance->get()['expose-global-functions']);
	}

	public function testPrefixNotSet(): void
	{
		$this->dumpTemporaryFile(
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

		$instance = new PHPScoperInc($this->getTemporaryPath());

		$this->assertNull($instance->get()['prefix']);
	}

	public function testPrefix(): void
	{
		$this->dumpTemporaryFile(
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
		$instance = new PHPScoperInc($this->getTemporaryPath());

		$this->assertSame('PVA\\Vendor', $instance->get()['prefix']);
	}

	public function testOverridePrefix(): void
	{
		$this->dumpTemporaryFile(
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
		$instance = new PHPScoperInc($this->getTemporaryPath(), ['prefix' => 'FOO\\Bar']);

		$this->assertSame('PVA\\Vendor', $instance->get()['prefix']);
	}

	public function testExcludeNamespaces(): void
	{
		$this->dumpTemporaryFile(
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
		$instance = new PHPScoperInc($this->getTemporaryPath());

		$this->assertContains('PluginName', $instance->get()['exclude-namespaces']);
	}

	public function testAdditionalExcludeNamespaces(): void
	{
		$this->dumpTemporaryFile(
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
		$instance = new PHPScoperInc($this->getTemporaryPath());

		$this->assertContains('PluginName', $instance->get()['exclude-namespaces']);
		$this->assertContains('Whoops', $instance->get()['exclude-namespaces']);
		$this->assertContains('Symfony\\Component\\Console', $instance->get()['exclude-namespaces']);
	}

	public function testExcludeFiles(): void
	{
		$this->dumpTemporaryFile(
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
		$this->dumpTemporaryFile('/vendor/foo.css', '');
		$this->dumpTemporaryFile('/vendor/foo.html', '');
		$this->dumpTemporaryFile('/vendor/foo.js', '');
		$this->dumpTemporaryFile('/vendor/foo.html.php', '');

		$instance = new PHPScoperInc($this->getTemporaryPath());
		$instance = $instance->excludeFiles([
			$this->getTemporaryPath('/vendor/foo.css'),
			$this->getTemporaryPath('/vendor/foo.html'),
			$this->getTemporaryPath('/vendor/foo.js'),
			$this->getTemporaryPath('/vendor/foo.html.php'),
		]);

		$this->assertContains($this->getTemporaryPath('/vendor/foo.css'), $instance->get()['exclude-files']);
		$this->assertContains($this->getTemporaryPath('/vendor/foo.html'), $instance->get()['exclude-files']);
		$this->assertContains($this->getTemporaryPath('/vendor/foo.js'), $instance->get()['exclude-files']);
		$this->assertContains($this->getTemporaryPath('/vendor/foo.html.php'), $instance->get()['exclude-files']);
	}

	public function testAddFinder(): void
	{
		$this->dumpTemporaryFile(
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

		$instance = new PHPScoperInc($this->getTemporaryPath());

		$finder = Finder::create();
		$instance = $instance->addFinder($finder);

		$this->assertContains($finder, $instance->get()['finders']);
	}

	public function testAddPatcher(): void
	{
		$this->dumpTemporaryFile(
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
		$instance = new PHPScoperInc($this->getTemporaryPath());
		$instance = $instance->addPatcher($patcher);

		$this->assertContains($patcher, $instance->get()['patchers']);
	}
}
