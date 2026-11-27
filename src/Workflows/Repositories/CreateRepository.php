<?php

declare(strict_types=1);

namespace Dunlavystudio\GhWizard\Workflows\Repositories;

use Dunlavystudio\GhWizard\Services\GitHubService;
use Dunlavystudio\GhWizard\Services\GitService;
use Dunlavystudio\GhWizard\Workflows\Navigation;
use Symfony\Component\Process\Exception\ProcessFailedException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

final class CreateRepository
{
    public function __construct(
        private readonly GitHubService $github,
    ) {}

    public function run(
        bool $publishLocal = false,
    ): Navigation {

        $source = null;
        $branch = null;

        if ($publishLocal) {
            $git = new GitService;

            try {
                $source = $git->getRepositoryRoot();

                if (! $git->hasCommits()) {
                    error('The repository has no commits.');

                    return Navigation::Back;
                }

                if ($git->hasRemotes()) {
                    error('This repository already has a remote.');

                    return Navigation::Back;
                }

                if ($git->hasUncommittedChanges()) {
                    error('Commit your local changes before publishing.');

                    return Navigation::Back;
                }

                $branch = $git->getCurrentBranch();

                info('Local Git repository detected.');
                info("Location: {$source}");
                info("Branch: {$branch}");

            } catch (ProcessFailedException $exception) {
                error(
                    trim($exception->getProcess()->getErrorOutput())
                        ?: $exception->getMessage()
                );

                return Navigation::Back;
            }
        }

        try {
            info('Retrieving GitHub account...');

            $user = $this->github->getAuthenticatedUser();

            info('Retrieving organisations...');

            $organisations = $this->github->getOrganisations();

            $owners = [
                $user => "{$user} (Personal account)",
            ];

            foreach ($organisations as $organisation) {
                $owners[$organisation] = $organisation;
            }

            $owner = select(
                label: 'Select repository owner',
                options: $owners,
            );

            $name = text(
                label: 'Repository name',
                placeholder: 'my-new-project',
                default: $source !== null ? basename($source) : '',
                required: true,
                validate: fn (string $value) => preg_match('/^[a-zA-Z0-9._-]{1,100}$/D', $value)
                && ! in_array($value, ['.', '..'], true)
                    ? null
                    : 'Enter a valid repository name.',
            );

            $description = text(
                label: 'Repository description',
                placeholder: 'Optional',
            );

            $visibility = select(
                label: 'Repository visibility',
                options: [
                    'private' => 'Private',
                    'public' => 'Public',
                ],
                default: 'private',
            );

            $rows = [
                ['Owner', $owner],
                ['Name', $name],
                ['Description', $description ?: 'None'],
                ['Visibility', ucfirst($visibility)],
            ];

            if ($source !== null) {
                $rows[] = ['Local source', $source];
                $rows[] = ['Branch', $branch];
                $rows[] = ['Remote', 'origin'];
                $rows[] = ['Push commits', 'Yes'];
            }

            table(
                headers: ['Property', 'Value'],
                rows: $rows,
            );

            if ($publishLocal) {
                info(
                    'Your committed code and associated Git history '
                    .'will be uploaded to GitHub. Check for secrets '
                    .'before continuing.'
                );
            }

            if (! confirm(
                label: 'Create this repository?',
                default: false,
            )) {
                info('Repository creation cancelled.');

                return Navigation::Back;
            }

            info('Creating repository...');

            $result = $this->github->createRepository(
                owner: $owner,
                name: $name,
                visibility: $visibility,
                description: $description,
                source: $source,
            );

            info('Repository created successfully!');
            info($result);

            return $this->postCreationActions($owner, $name);

        } catch (ProcessFailedException $exception) {
            error(
                trim($exception->getProcess()->getErrorOutput())
                    ?: $exception->getMessage()
            );

            return Navigation::Back;
        }
    }

    private function postCreationActions(
        string $owner,
        string $name,
    ): Navigation {
        while (true) {
            $action = select(
                label: 'What would you like to do next?',
                options: [
                    'browser' => 'Open repository in browser',
                    'clone' => 'Clone repository locally',
                    'back' => 'Return to repositories',
                    'exit' => 'Exit GitHub Wizard',
                ],
                default: 'browser',
            );

            try {
                switch ($action) {
                    case 'browser':
                        $this->github->openRepository(
                            $owner,
                            $name,
                        );

                        info('Repository opened in browser.');
                        break;

                    case 'clone':
                        $this->cloneRepository($owner, $name);
                        break;

                    case 'back':
                        return Navigation::Back;

                    case 'exit':
                        return Navigation::Exit;
                }
            } catch (ProcessFailedException $exception) {
                error(
                    trim($exception->getProcess()->getErrorOutput())
                        ?: $exception->getMessage()
                );
            }
        }
    }

    private function cloneRepository(
        string $owner,
        string $name,
    ): void {
        $directory = text(
            label: 'Where would you like to clone the repository?',
            default: getcwd().DIRECTORY_SEPARATOR.$name,
            required: true,
            validate: fn (string $value) => str_starts_with($value, '/')
                ? null
                : 'Please enter an absolute directory path.',
        );

        if (file_exists($directory) || is_link($directory)) {
            error('The destination already exists.');

            return;
        }

        if (! confirm(
            label: "Clone repository to {$directory}?",
            default: false,
        )) {
            info('Clone cancelled.');

            return;
        }

        info('Cloning repository...');

        $this->github->cloneRepository(
            owner: $owner,
            name: $name,
            directory: $directory,
        );

        info('Repository cloned successfully!');
        info("Location: {$directory}");
    }
}
