<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Symfony\Component\Console\Input\ArrayInput;
use Syntatis\Codex\Companion\Console\ProjectInitCommand;
use Syntatis\Codex\Companion\Console\ScoperInitCommand;

/** @codeCoverageIgnore */
class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
	public function activate(Composer $composer, IOInterface $io): void
	{
	}

	public function deactivate(Composer $composer, IOInterface $io): void
	{
	}

	public function uninstall(Composer $composer, IOInterface $io): void
	{
	}

	/** @return array<string,string|array{0:string,1?:int}|array<array{0:string,1?:int}>> The event names to listen to */
	public static function getSubscribedEvents(): array
	{
		return [
			'post-create-project-cmd' => [
				['onPostCreateProject'],
			],
			'post-autoload-dump' => [
				['onPostAutoloadDump'],
			],
		];
	}

	public function onPostCreateProject(Event $event): void
	{
		$vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

		ProjectInitCommand::executeOnComposer(
			$vendorDir,
			new ArrayInput([]),
			Factory::createOutput(),
		);

		self::runScoper($vendorDir);
	}

	public function onPostAutoloadDump(Event $event): void
	{
		if (($GLOBALS['argv'][1] ?? null) === 'create-project') {
			return;
		}

		self::runScoper($event->getComposer()->getConfig()->get('vendor-dir'));
	}

	private static function runScoper(string $vendorDir): void
	{
		ScoperInitCommand::executeOnComposer(
			$vendorDir,
			new ArrayInput(['--yes' => true]),
			Factory::createOutput(),
		);
	}
}
