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
			'post-install-cmd' => [
				['onPostInstall', 1],
			],
			'post-update-cmd' => [
				['onPostUpdate', 1],
			],
		];
	}

	public function onPostCreateProject(Event $event): void
	{
		$codex = $this->codex($event);
		$output = Factory::createOutput();

		ProjectInitCommand::executeOnComposer(
			$codex,
			new ArrayInput([]),
			$output,
		);
		ScoperInitCommand::executeOnComposer(
			$codex,
			new ArrayInput(['--yes' => true]),
			$output,
		);
	}

	public function onPostInstall(Event $event): void
	{
		if (($GLOBALS['argv'][1] ?? null) === 'create-project') {
			return;
		}

		$codex = $this->codex($event);
		$output = Factory::createOutput();

		ScoperInitCommand::executeOnComposer(
			$codex,
			new ArrayInput(['--yes' => true]),
			$output,
		);
	}

	public function onPostUpdate(Event $event): void
	{
		$this->onPostInstall($event);
	}

	private function codex(Event $event): Codex
	{
		$vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

		return new Codex(dirname($vendorDir));
	}
}
