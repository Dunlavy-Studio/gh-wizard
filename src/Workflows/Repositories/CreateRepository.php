<?php

declare(strict_types=1);

namespace Dunlavystudio\GhWizard\Workflows\Repositories;

use Dunlavystudio\GhWizard\Services\GitHubService;
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

    public function run(): void
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

                return;
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

        } catch (ProcessFailedException $exception) {
            error(
                trim($exception->getProcess()->getErrorOutput())
                    ?: $exception->getMessage()
            );
        }
    }
}