<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console\Processes;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Concerns\RunProcess;
use Syntatis\Codex\Companion\Contracts\Executable;
use Syntatis\Codex\Companion\Helpers\WPPluginProps;
use Throwable;

use function sprintf;

class WPPluginVersionInfoProcess implements Executable
{
	use RunProcess;

	private Codex $codex;

	/**
	 * // phpcs:ignore
	 * @param StyleInterface&OutputInterface $output
	 */
	public function __construct(Codex $codex, $output)
	{
		$this->codex = $codex;
		$this->output = $output;
	}

	public function execute(): int
	{
		try {
			$props = new WPPluginProps($this->codex);

			$this->output->listing([
				sprintf('<info>Version:</info> <comment>%s</comment>', (string) $props->getVersion('wp_plugin_version')),
				sprintf('<info>Tested up to:</info> <comment>%s</comment>', (string) $props->getVersion('wp_plugin_tested_up_to')),
			]);
		} catch (Throwable $th) {
			$this->output->error($th->getMessage());

			return 1;
		}

		return 0;
	}
}
