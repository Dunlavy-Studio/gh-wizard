<?php

declare(strict_types=1);

namespace Dunlavystudio\GhWizard\Commands;

use Dunlavystudio\GhWizard\Services\GitHubService;
use Dunlavystudio\GhWizard\Workflows\Repositories\CreateRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;

final class WizardCommand extends Command
{
    public function __construct()
    {
        parent::__construct('wizard');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        intro('GitHub Wizard');

        $github = new GitHubService();

        while (true) {
            $action = select(
                label: 'What would you like to do?',
                options: [
                    'repositories' => 'Repositories',
                    'exit' => 'Exit',
                ],
            );

            if ($action === 'exit') {
                outro('Happy coding!');

                return Command::SUCCESS;
            }

            if ($action === 'repositories') {
                $this->repositories($github);
            }
        }
    }

    private function repositories(GitHubService $github): void
    {
        while (true) {
            $action = select(
                label: 'Repositories',
                options: [
                    'create' => 'Create a repository',
                    'back' => 'Back',
                ],
            );

            if ($action === 'back') {
                return;
            }

            if ($action === 'create') {
                (new CreateRepository($github))->run();
            }
        }
    }
}