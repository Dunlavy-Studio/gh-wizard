<?php

declare(strict_types=1);

use Dunlavystudio\GhWizard\Services\GitService;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

beforeEach(function () {
    $this->directory = sys_get_temp_dir()
        . DIRECTORY_SEPARATOR
        . 'gh-wizard-' . bin2hex(random_bytes(8));

    mkdir($this->directory, 0700);

    $this->git = new GitService($this->directory);
});

afterEach(function () {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $this->directory,
            FilesystemIterator::SKIP_DOTS,
        ),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($files as $file) {
        if ($file->isDir() && !$file->isLink()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }

    rmdir($this->directory);
});

function runGit(string $directory, array $arguments): void
{
    $process = new Process(
        ['git', ...$arguments],
        $directory,
    );

    $process->mustRun();
}

function initialiseRepository(string $directory): void
{
    runGit($directory, ['init', '-b', 'main']);

    runGit($directory, [
        'config', 'user.name', 'Test User',
    ]);

    runGit($directory, [
        'config', 'user.email', 'test@example.com',
    ]);

    runGit($directory, [
        'config', 'commit.gpgsign', 'false',
    ]);
}

function createCommit(string $directory): void
{
    file_put_contents(
        $directory . '/README.md',
        '# Test Repository',
    );

    runGit($directory, ['add', 'README.md']);
    runGit($directory, ['commit', '-m', 'Initial commit']);
}

it('rejects directories without a Git repository', function () {
    expect(fn () => $this->git->getRepositoryRoot())
        ->toThrow(ProcessFailedException::class);
});

it('retrieves the repository root', function () {
    initialiseRepository($this->directory);

    expect($this->git->getRepositoryRoot())
        ->toBe(realpath($this->directory));
});

it('retrieves the repository root from a subdirectory', function () {
    initialiseRepository($this->directory);

    $subdirectory = $this->directory . '/src';

    mkdir($subdirectory);

    $git = new GitService($subdirectory);

    expect($git->getRepositoryRoot())
        ->toBe(realpath($this->directory));
});

it('detects repositories without commits', function () {
    initialiseRepository($this->directory);

    expect($this->git->hasCommits())->toBeFalse();
});

it('detects repositories with commits', function () {
    initialiseRepository($this->directory);
    createCommit($this->directory);

    expect($this->git->hasCommits())->toBeTrue();
});

it('detects repositories without remotes', function () {
    initialiseRepository($this->directory);

    expect($this->git->hasRemotes())->toBeFalse();
});

it('detects repositories with an existing remote', function () {
    initialiseRepository($this->directory);

    runGit($this->directory, [
        'remote',
        'add',
        'origin',
        'https://github.com/example/test.git',
    ]);

    expect($this->git->hasRemotes())->toBeTrue();
});

it('detects a clean working tree', function () {
    initialiseRepository($this->directory);
    createCommit($this->directory);

    expect($this->git->hasUncommittedChanges())
        ->toBeFalse();
});

it('detects untracked files', function () {
    initialiseRepository($this->directory);

    file_put_contents(
        $this->directory . '/new-file.txt',
        'Untracked content',
    );

    expect($this->git->hasUncommittedChanges())
        ->toBeTrue();
});

it('detects modified tracked files', function () {
    initialiseRepository($this->directory);
    createCommit($this->directory);

    file_put_contents(
        $this->directory . '/README.md',
        'Modified content',
    );

    expect($this->git->hasUncommittedChanges())
        ->toBeTrue();
});

it('detects staged changes', function () {
    initialiseRepository($this->directory);

    file_put_contents(
        $this->directory . '/new-file.txt',
        'Staged content',
    );

    runGit($this->directory, ['add', 'new-file.txt']);

    expect($this->git->hasUncommittedChanges())
        ->toBeTrue();
});

it('retrieves the current branch', function () {
    initialiseRepository($this->directory);

    expect($this->git->getCurrentBranch())->toBe('main');
});

it('detects a different branch', function () {
    initialiseRepository($this->directory);
    createCommit($this->directory);

    runGit($this->directory, [
        'switch', '-c', 'feature/example',
    ]);

    expect($this->git->getCurrentBranch())
        ->toBe('feature/example');
});

it('rejects a detached HEAD', function () {
    initialiseRepository($this->directory);
    createCommit($this->directory);

    runGit($this->directory, [
        'checkout', '--detach', 'HEAD',
    ]);

    expect(fn () => $this->git->getCurrentBranch())
        ->toThrow(ProcessFailedException::class);
});