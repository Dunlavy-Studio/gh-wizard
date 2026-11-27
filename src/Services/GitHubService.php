<?php

declare(strict_types=1);

namespace Dunlavystudio\GhWizard\Services;

use InvalidArgumentException;
use Symfony\Component\Process\Process;

final class GitHubService
{
    public function getAuthenticatedUser(): string
    {
        return $this->execute([
            'api',
            'user',
            '--jq',
            '.login',
        ]);
    }

    /**
     * @return list<string>
     */
    public function getOrganisations(): array
    {
        $output = $this->execute([
            'api',
            'user/orgs',
            '--paginate',
            '--jq',
            '.[].login',
        ]);

        if ($output === '') {
            return [];
        }

        return explode("\n", $output);
    }

    public function createRepository(
        string $owner,
        string $name,
        string $visibility,
        string $description = '',
        ?string $source = null,
    ): string {
        if (!in_array($visibility, ['public', 'private'], true)) {
            throw new \InvalidArgumentException(
                'Invalid repository visibility.'
            );
        }

        $arguments = [
            'repo',
            'create',
            "{$owner}/{$name}",
            "--{$visibility}",
        ];

        if ($description !== '') {
            $arguments[] = '--description';
            $arguments[] = $description;
        }

        if ($source !== null) {
            $arguments[] = '--source';
            $arguments[] = $source;
            $arguments[] = '--remote=origin';
            $arguments[] = '--push';
        }

        return $this->execute($arguments);
    }
    /**
     * @param list<string> $arguments
     */
    private function execute(array $arguments): string
    {
        $process = new Process([
            'gh',
            ...$arguments,
        ]);

        $process->mustRun();

        return trim($process->getOutput());
    }

    public function openRepository(
        string $owner,
        string $name,
    ): void {
        $this->execute([
            'repo',
            'view',
            "{$owner}/{$name}",
            '--web',
        ]);
    }

    public function cloneRepository(
        string $owner,
        string $name,
        string $directory,
    ): void {
        $this->execute([
            'repo',
            'clone',
            "{$owner}/{$name}",
            $directory,
        ]);
    }
}

