<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console\Helpers;

use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Process\Process;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Utils\Val;

use function explode;
use function trim;

class ShellProcess
{
	private Codex $codex;

	private StyleInterface $style;

	private Process $process;

	private int $exitCode = 0;

	private string $preMessage = '';

	/** @var array<int,string> */
	private array $messages = [];

	public function __construct(Codex $codex, StyleInterface $style)
	{
		$this->codex = $codex;
		$this->style = $style;
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
	public function run(string $command, ?string $cwd = null): self
	{
		$self = clone $this;
		$self->process = $this->create($command, $cwd);

		if (! Val::isBlank($self->preMessage)) {
			$self->style->text($self->preMessage);
		}

		$self->exitCode = $self->process->run();

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

	private function create(string $command, ?string $cwd = null): Process
	{
		return new Process(explode(' ', $command), $cwd ?? $this->codex->getProjectPath());
	}
}