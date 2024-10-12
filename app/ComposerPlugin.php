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

use function dirname;

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
				['onPostCreateProject', 1],
			],
			'post-autoload-dump' => [
				['onPostAutoloadDump', 1],
			],
		];
	}

	public function onPostCreateProject(Event $event): void
	{
		// There is a descrepancy in the return type in Composer older versions,
		// but safely assume that it will always be a string.
		// @phpstan-disable-next-line
		/** @var string $vendorDir */
		$vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

		ProjectInitCommand::executeOnComposer(
			dirname($vendorDir),
			new ArrayInput([]),
			Factory::createOutput(),
		);

		ScoperInitCommand::executeOnComposer(
			/**
			 * Always create a new `Codex` instance to get the new information. It is
			 * because some of the project properties such as the plugin name, the
			 * plugin slug, the namespace, etc. may have been updated after the
			 * project initialization from the previous command.
			 */
			dirname($vendorDir),
			new ArrayInput(['--yes' => true]),
			Factory::createOutput(),
		);
	}

	public function onPostAutoloadDump(Event $event): void
	{
		if (($GLOBALS['argv'][1] ?? null) === 'create-project') {
			return;
		}

		// There is a descrepancy in the return type in Composer older versions,
		// but safely assume that it will always be a string.
		// @phpstan-disable-next-line
		/** @var string $vendorDir */
		$vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
		$output = Factory::createOutput();

		ScoperInitCommand::executeOnComposer(
			dirname($vendorDir),
			new ArrayInput(['--yes' => true]),
			Factory::createOutput(),
		);
	}
}
