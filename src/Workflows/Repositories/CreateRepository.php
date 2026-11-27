<?php

declare(strict_types=1);

namespace Dunlavystudio\GhWizard\Workflows\Repositories;

use Dunlavystudio\GhWizard\Services\GitHubService;
use Dunlavystudio\GhWizard\Workflows\Navigation;
use Symfony\Component\Process\Exception\ProcessFailedException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;
use function Laravel\Prompts\table;

final class CreateRepository
{
    public function __construct(
        private readonly GitHubService $github,
    ) {}

    public function run(): Navigation
    {
        try {
            $user = spin(
                callback: fn () => $this->github->getAuthenticatedUser(),
                message: 'Retrieving GitHub account...',
            );

            $organisations = spin(
                callback: fn () => $this->github->getOrganisations(),
                message: 'Retrieving organisations...',
            );

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
                required: true,
                validate: fn (string $value) =>
                preg_match('/^[a-zA-Z0-9._-]{1,100}$/D', $value)
                && !in_array($value, ['.', '..'], true)
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

            table(
                headers: ['Property', 'Value'],
                rows: [
                    ['Owner', $owner],
                    ['Name', $name],
                    ['Description', $description ?: 'None'],
                    ['Visibility', ucfirst($visibility)],
                ],
            );

            if (!confirm(
                label: 'Create this repository?',
                default: false,
            )) {
                info('Repository creation cancelled.');

                return Navigation::Back;
            }

            $result = spin(
                callback: fn () => $this->github->createRepository(
                    owner: $owner,
                    name: $name,
                    visibility: $visibility,
                    description: $description,
                ),
                message: 'Creating repository...',
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
            validate: fn (string $value) =>
            str_starts_with($value, '/')
                ? null
                : 'Please enter an absolute directory path.',
        );

        if (file_exists($directory) || is_link($directory)) {
            error('The destination already exists.');

            return;
        }

        if (!confirm(
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