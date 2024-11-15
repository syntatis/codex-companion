<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console\Processes;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Codex\Companion\Concerns\RunProcess;
use Syntatis\Codex\Companion\Contracts\Executable;
use Syntatis\Codex\Companion\Contracts\Versionable;
use Syntatis\Codex\Companion\Helpers\Versions\WPPluginVersion;
use Syntatis\Codex\Companion\Helpers\WPPluginProps;
use Version\Version;

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
		/**
		 * @var string $versionLabel
		 * @phpstan-var "Stable tag"|"Requires at least"|"Requires PHP" $versionLabel
		 */
		$versionLabel = $this->output->choice('Which version would you like to bump?', [
			'Stable tag',
			'Requires at least',
			'Requires PHP',
		], 'Stable tag');

		$versionPart = $this->output->choice(
			sprintf('Select "%s" version part to bump', $versionLabel),
			$versionLabel === 'Stable tag' ? [
				'major',
				'minor',
				'patch',
			] : [
				'major',
				'minor',
			],
		);

		$wpPluginProps = new WPPluginProps($this->codex);
		$currentVersion = $wpPluginProps->getVersion($this->getVersionKey($versionLabel));

		$this->output->confirm(
			sprintf(
				'Bump % version from %s. Would you like to bump it?',
				$versionLabel,
				$currentVersion,
				(string) Version::fromString($currentVersion)->incrementPatch(),
			),
		);

		return 0;
	}

	private function getVersionKey(string $label): string
	{
		if ($label === 'Stable tag') {
			return 'wp_plugin_version';
		}

		return 'version';
	}

	private function getIncrementedVersion(string $label, string $currentVersion, string $versionParth): string
	{
		switch ($label) {
			case 'Stable tag':
				$versionable = new WPPluginVersion($currentVersion);
				break;
		}

		$versionable = Version::fromString($currentVersion);

		if (! ($versionable instanceof Versionable)) {
			return;
		}

		$versionable->incrementPatch();
	}
}
