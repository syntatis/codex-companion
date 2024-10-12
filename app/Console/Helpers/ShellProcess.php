<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console\Helpers;

use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Process\Process;
use Syntatis\Utils\Val;

use function explode;
use function trim;

class ShellProcess
{
	private ?string $cwd = null;

	private StyleInterface $style;

	private Process $process;

	private int $exitCode = 0;

	private string $preMessage = '';

	/** @var array<int,string> */
	private array $messages = [];

	public function __construct(StyleInterface $style, ?string $cwd = null)
	{
		$this->style = $style;
		$this->cwd = $cwd;
	}

	/** @phpstan-param non-empty-string $message */
	public function withMessage(string $message): self
	{
		$self = clone $this;
		$self->preMessage = $message;

		return $self;
	}

	/** @phpstan-param non-empty-string $message */
	public function withSuccessMessage(string $message): self
	{
		$self = clone $this;
		$self->messages[0] = $message;

		return $self;
	}

	/** @phpstan-param non-empty-string $message */
	public function withErrorMessage(string $message): self
	{
		$self = clone $this;
		$self->messages[1] = $message;

		return $self;
	}

	/** @phpstan-param non-empty-string $command */
	public function run(string $command, ?callable $callback = null): self
	{
		$self = clone $this;
		$self->process = self::create($command, $this->cwd);

		if (! Val::isBlank($self->preMessage)) {
			$self->style->text($self->preMessage);
		}

		$self->exitCode = $self->process->run($callback);

		$output = trim($self->process->getOutput());
		$errorOutput = trim($self->process->getErrorOutput());
		$userMessage = $self->messages[$self->exitCode] ?? null;

		if ($self->isSuccessful()) {
			if (! Val::isBlank($userMessage)) {
				$self->style->success($userMessage);
			}

			return $self;
		}

		$errorMessage = Val::isBlank($errorOutput) ?
			'An unknown error occurred while executing the command.' :
			$errorOutput;

		if (Val::isBlank($userMessage)) {
			$self->style->error($errorMessage);
		} else {
			$self->style->error($userMessage);
			$self->style->note($errorMessage);
		}

		return $self;
	}

	public function getCurrent(): Process
	{
		return $this->process;
	}

	public function getExitCode(): int
	{
		return $this->process->getExitCode() ?? $this->exitCode;
	}

	public function isSuccessful(): bool
	{
		return $this->process->isSuccessful();
	}

	public function isFailed(): bool
	{
		return ! $this->process->isSuccessful();
	}

	private static function create(string $command, ?string $cwd = null): Process
	{
		return new Process(explode(' ', $command), $cwd);
	}
}
