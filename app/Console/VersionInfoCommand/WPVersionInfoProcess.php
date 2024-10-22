<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console\VersionInfoCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Concerns\RunProcess;
use Syntatis\Codex\Companion\Contracts\Executable;
use Syntatis\Codex\Companion\Projects\Howdy\VersioningProps;
use Throwable;

use function sprintf;

class WPVersionInfoProcess implements Executable
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
			$props = new VersioningProps($this->codex);
			$props = $props->get();

			$wpVersion = $props['wp_version'];
			$wpTested = $props['wp_tested'];

			$this->output->text(sprintf('Version: %s', $wpVersion));
			$this->output->text(sprintf('Tested up to: %s', $wpTested));
		} catch (Throwable $th) {
			$this->output->error($th->getMessage());

			return 1;
		}

		return 0;
	}
}
