<?php

declare(strict_types=1);

namespace Dunlavystudio\GhWizard\Services;

use Symfony\Component\Process\Process;

final class GitService
{
    public function getRepositoryRoot(): string
    {
        return $this->execute([
            'rev-parse',
            '--show-toplevel',
        ]);
    }

    public function hasCommits(): bool
    {
        $process = new Process([
            'git',
            'rev-parse',
            '--verify',
            'HEAD',
        ]);

        $process->run();

        return $process->isSuccessful();
    }

    public function hasRemotes(): bool
    {
        return $this->execute(['remote']) !== '';
    }

    public function hasUncommittedChanges(): bool
    {
        return $this->execute([
                'status',
                '--porcelain',
            ]) !== '';
    }

    public function getCurrentBranch(): string
    {
        return $this->execute([
            'symbolic-ref',
            '--short',
            'HEAD',
        ]);
    }

    /**
     * @param list<string> $arguments
     */
    private function execute(array $arguments): string
    {
        $process = new Process([
            'git',
            ...$arguments,
        ]);

        $process->mustRun();

        return trim($process->getOutput());
    }
}