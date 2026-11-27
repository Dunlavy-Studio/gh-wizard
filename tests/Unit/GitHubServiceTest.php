<?php

declare(strict_types=1);

use Dunlavystudio\GhWizard\Services\GitHubService;

it('retrieves the authenticated username', function () {
    $github = new GitHubService(
        runner: function (array $command): string {
            expect($command)->toBe([
                'gh',
                'api',
                'user',
                '--jq',
                '.login',
            ]);

            return "test-user\n";
        },
    );

    expect($github->getAuthenticatedUser())
        ->toBe('test-user');
});

it('retrieves available organisations', function () {
    $github = new GitHubService(
        runner: function (array $command): string {
            expect($command)->toBe([
                'gh',
                'api',
                'user/orgs',
                '--paginate',
                '--jq',
                '.[].login',
            ]);

            return "organisation-one\norganisation-two\n";
        },
    );

    expect($github->getOrganisations())->toBe([
        'organisation-one',
        'organisation-two',
    ]);
});

it('handles accounts without organisations', function () {
    $github = new GitHubService(
        runner: fn (array $command): string => '',
    );

    expect($github->getOrganisations())->toBe([]);
});

it('creates a private repository', function () {
    $github = new GitHubService(
        runner: function (array $command): string {
            expect($command)->toBe([
                'gh',
                'repo',
                'create',
                'test-org/test-repo',
                '--private',
            ]);

            return "https://github.com/test-org/test-repo\n";
        },
    );

    expect($github->createRepository(
        owner: 'test-org',
        name: 'test-repo',
        visibility: 'private',
    ))->toBe('https://github.com/test-org/test-repo');
});

it('creates a public repository', function () {
    $github = new GitHubService(
        runner: function (array $command): string {
            expect($command)->toBe([
                'gh',
                'repo',
                'create',
                'test-org/test-repo',
                '--public',
            ]);

            return 'https://github.com/test-org/test-repo';
        },
    );

    expect($github->createRepository(
        owner: 'test-org',
        name: 'test-repo',
        visibility: 'public',
    ))->toBe('https://github.com/test-org/test-repo');
});

it('rejects invalid repository visibility', function () {
    $github = new GitHubService(
        runner: function (array $command): string {
            throw new LogicException(
                'The command should not be executed.'
            );
        },
    );

    expect(fn () => $github->createRepository(
        owner: 'test-org',
        name: 'test-repo',
        visibility: 'invalid',
    ))->toThrow(
        InvalidArgumentException::class,
        'Invalid repository visibility.',
    );
});

it('includes a repository description when provided', function () {
    $github = new GitHubService(
        runner: function (array $command): string {
            expect($command)->toBe([
                'gh',
                'repo',
                'create',
                'test-org/test-repo',
                '--private',
                '--description',
                'My example repository',
            ]);

            return 'https://github.com/test-org/test-repo';
        },
    );

    $github->createRepository(
        owner: 'test-org',
        name: 'test-repo',
        visibility: 'private',
        description: 'My example repository',
    );
});

it('publishes an existing local repository', function () {
    $github = new GitHubService(
        runner: function (array $command): string {
            expect($command)->toBe([
                'gh',
                'repo',
                'create',
                'test-org/test-repo',
                '--private',
                '--source',
                '/code/test-repo',
                '--remote=origin',
                '--push',
            ]);

            return 'https://github.com/test-org/test-repo';
        },
    );

    $github->createRepository(
        owner: 'test-org',
        name: 'test-repo',
        visibility: 'private',
        source: '/code/test-repo',
    );
});

it('opens a repository in the browser', function () {
    $github = new GitHubService(
        runner: function (array $command): string {
            expect($command)->toBe([
                'gh',
                'repo',
                'view',
                'test-org/test-repo',
                '--web',
            ]);

            return '';
        },
    );

    $github->openRepository(
        owner: 'test-org',
        name: 'test-repo',
    );
});

it('clones a repository into the selected directory', function () {
    $github = new GitHubService(
        runner: function (array $command): string {
            expect($command)->toBe([
                'gh',
                'repo',
                'clone',
                'test-org/test-repo',
                '/code/test-repo',
            ]);

            return '';
        },
    );

    $github->cloneRepository(
        owner: 'test-org',
        name: 'test-repo',
        directory: '/code/test-repo',
    );
});

it('propagates failed GitHub commands', function () {
    $github = new GitHubService(
        runner: function (array $command): string {
            throw new RuntimeException('Command failed.');
        },
    );

    expect(fn () => $github->getAuthenticatedUser())
        ->toThrow(
            RuntimeException::class,
            'Command failed.',
        );
});