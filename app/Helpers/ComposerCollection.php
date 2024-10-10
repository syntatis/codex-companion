<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers;

use Adbar\Dot;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use Syntatis\Codex\Companion\Exceptions\MissingRequiredFile;
use Syntatis\Utils\Val;

use function file_get_contents;
use function is_file;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * Handles data collection from composer.json file.
 *
 * @phpstan-extends Dot<string,mixed>
 */
class ComposerCollection extends Dot
{
	/** @param string $path The path to the Composer file. */
	public function __construct(string $path)
	{
		$path = Path::normalize($path . '/composer.json');

		if (! is_file($path)) {
			throw new MissingRequiredFile('composer.json');
		}

		$content = file_get_contents($path);

		if (Val::isBlank($content)) {
			throw new RuntimeException('Invalid composer.json content');
		}

		parent::__construct(json_decode($content, true, 512, JSON_THROW_ON_ERROR));
	}
}
