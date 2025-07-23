<?php

declare(strict_types=1);

namespace Syntatis\Tests\Clients;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Syntatis\Codex\Companion\Clients\PHPScoperInc;
use Syntatis\Tests\WithTemporaryFiles;

use function array_map;

class PHPScoperIncTest extends TestCase
{
	use WithTemporaryFiles;

	public function setUp(): void
	{
		// $this->markTestIncomplete('Incompatible with "Isolated\Symfony\Component\Finder\Finder" use.');
		parent::setUp();

		$this->dumpTemporaryFile(
			'composer.json',
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
		self::$filesystem->mkdir($this->getTemporaryPath('vendor'));
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
		$this->dumpTemporaryFile(
			'composer.json',
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
							"prefix": "PVA\\Vendor",
							"expose-global-constants": false,
							"expose-global-classes": false,
							"expose-global-functions": true
						}
					}
				}
			}
			CONTENT,
		);

		$instance = new PHPScoperInc($this->getTemporaryPath());

		$this->assertFalse($instance->get()['expose-global-constants']);
		$this->assertFalse($instance->get()['expose-global-classes']);
		$this->assertTrue($instance->get()['expose-global-functions']);
	}

	public function testPrefixNotSet(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
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
			'composer.json',
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
			'composer.json',
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
			'composer.json',
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
			'composer.json',
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

	public function testExcludeFilesFieldInAndName(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": ["app/"]
					}
				},
				"extra": {
					"codex": {
						"scoper": {
							"prefix": "PVA\\Vendor",
							"exclude-files": [
								{"in": "tmp/phpunit-dumps/files", "name": ["foo.css"]}
							]
						}
					}
				}
			}
			CONTENT,
		);

		$this->dumpTemporaryFile('files/foo.css', '');
		$this->dumpTemporaryFile('files/foo.html', '');

		$instance = new PHPScoperInc($this->getTemporaryPath());
		$excludeFiles = array_map(
			static fn ($file) => Path::canonicalize($file),
			$instance->get()['exclude-files'],
		);

		$this->assertContains($this->getTemporaryPath('files/foo.css'), $excludeFiles);
		$this->assertNotContains($this->getTemporaryPath('files/foo.html'), $excludeFiles);
	}

	public function testExcludeFilesFieldOnlyIn(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": ["app/"]
					}
				},
				"extra": {
					"codex": {
						"scoper": {
							"prefix": "PVA\\Vendor",
							"exclude-files": [
								{"in": "tmp/phpunit-dumps/files"}
							]
						}
					}
				}
			}
			CONTENT,
		);

		$this->dumpTemporaryFile('files/foo.css', '');
		$this->dumpTemporaryFile('files/foo.html', '');

		$instance = new PHPScoperInc($this->getTemporaryPath());
		$excludeFiles = array_map(
			static fn ($file) => Path::canonicalize($file),
			$instance->get()['exclude-files'],
		);

		$this->assertContains($this->getTemporaryPath('files/foo.css'), $excludeFiles);
		$this->assertContains($this->getTemporaryPath('files/foo.html'), $excludeFiles);
	}

	public function testExcludeFilesFieldOnlyName(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": ["app/"]
					}
				},
				"extra": {
					"codex": {
						"scoper": {
							"prefix": "PVA\\Vendor",
							"exclude-files": [
								{"name": "foo.css"}
							]
						}
					}
				}
			}
			CONTENT,
		);

		$this->expectException(InvalidArgumentException::class);

		new PHPScoperInc($this->getTemporaryPath());
	}

	public function testExcludeFilesFieldInInvalid(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": ["app/"]
					}
				},
				"extra": {
					"codex": {
						"scoper": {
							"prefix": "PVA\\Vendor",
							"exclude-files": [
								{"in": 1}
							]
						}
					}
				}
			}
			CONTENT,
		);

		$this->expectException(InvalidArgumentException::class);

		new PHPScoperInc($this->getTemporaryPath());
	}

	public function testExcludeFilesFieldNameInvalid(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": ["app/"]
					}
				},
				"extra": {
					"codex": {
						"scoper": {
							"prefix": "PVA\\Vendor",
							"exclude-files": [
								{"in": "tmp/phpunit-dumps/files", "name": 1}
							]
						}
					}
				}
			}
			CONTENT,
		);

		$this->expectException(InvalidArgumentException::class);

		new PHPScoperInc($this->getTemporaryPath());
	}

	public function testExcludeFilesArrayInvalid(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": ["app/"]
					}
				},
				"extra": {
					"codex": {
						"scoper": {
							"prefix": "PVA\\Vendor",
							"exclude-files": "tmp/phpunit-dumps/files/foo.css"
						}
					}
				}
			}
			CONTENT,
		);

		$this->expectException(InvalidArgumentException::class);

		new PHPScoperInc($this->getTemporaryPath());
	}

	public function testExcludeFilesArray(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": ["app/"]
					}
				},
				"extra": {
					"codex": {
						"scoper": {
							"prefix": "PVA\\Vendor",
							"exclude-files": [
								{"in": "tmp/phpunit-dumps/files", "name": ["foo.css"]}
							]
						}
					}
				}
			}
			CONTENT,
		);

		$this->dumpTemporaryFile('files/foo.css', '');
		$this->dumpTemporaryFile('files/foo.html', '');

		$instance = new PHPScoperInc($this->getTemporaryPath());
		$excludeFiles = array_map(
			static fn ($file) => Path::canonicalize($file),
			$instance->get()['exclude-files'],
		);

		$this->assertContains($this->getTemporaryPath('files/foo.css'), $excludeFiles);
		$this->assertNotContains($this->getTemporaryPath('files/foo.html'), $excludeFiles);
	}

	public function testExcludeFilesArrayInvalidIn(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": ["app/"]
					}
				},
				"extra": {
					"codex": {
						"scoper": {
							"prefix": "PVA\\Vendor",
							"exclude-files": [
								{"in": ["tmp/phpunit-dumps/files", 1], "name": ["foo.css"]}
							]
						}
					}
				}
			}
			CONTENT,
		);

		$this->expectException(InvalidArgumentException::class);

		new PHPScoperInc($this->getTemporaryPath());
	}

	public function testExcludeFilesArrayInvalidName(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
			<<<'CONTENT'
			{
				"name": "syntatis/howdy",
				"autoload": {
					"psr-4": {
						"PluginName\\": ["app/"]
					}
				},
				"extra": {
					"codex": {
						"scoper": {
							"prefix": "PVA\\Vendor",
							"exclude-files": [
								{"in": ["tmp/phpunit-dumps/files"], "name": ["foo.css", 1]}
							]
						}
					}
				}
			}
			CONTENT,
		);

		$this->expectException(InvalidArgumentException::class);

		new PHPScoperInc($this->getTemporaryPath());
	}

	public function testWithFinder(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
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
		$instance = $instance->withFinder($finder);

		$this->assertContains($finder, $instance->get()['finders']);
	}

	public function testWithFinderConfigs(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
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
		$this->dumpTemporaryFile('vendor/foo/foo.html.php', '');

		$instance = new PHPScoperInc($this->getTemporaryPath());

		$finder = Finder::create();
		$instance = $instance->withFinder(['not-path' => ['foo']]);
		$finder = $instance->get()['finders'][0];

		$finderNotPaths = (new ReflectionClass($finder))->getProperty('notPaths');
		$finderNotPaths->setAccessible(true);

		$this->assertContains('foo', $finderNotPaths->getValue($finder));
	}

	public function testWithPatcher(): void
	{
		$this->dumpTemporaryFile(
			'composer.json',
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
		$instance = $instance->withPatcher($patcher);

		$this->assertContains($patcher, $instance->get()['patchers']);
	}
}
