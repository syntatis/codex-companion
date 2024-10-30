<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console\Processes;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Concerns\RunProcess;
use Syntatis\Codex\Companion\Contracts\Executable;
use Syntatis\Utils\Val;

use function sprintf;

class WPPluginVersionBumpProcess implements Executable
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
		/** @var string $version */
		$version = $this->output->choice('Which version would you like to bump?', [
			'Stable tag',
			'WordPress min. requirement',
			'PHP min. requirement',
		], 'Stable tag');

		if (! Val::isBlank($version)) {
			$choice = $this->output->choice(
				sprintf('Which "%s" version would you like to bump?', $version),
				[
					'major',
					'minor',
					'patch',
				],
			);
		}

		return 0;
	}
}
