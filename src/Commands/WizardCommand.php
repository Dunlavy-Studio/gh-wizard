<?php

declare(strict_types=1);

namespace Dunlavystudio\GhWizard\Commands;

use Dunlavystudio\GhWizard\Services\GitHubService;
use Dunlavystudio\GhWizard\Workflows\Navigation;
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

        $github = new GitHubService;

        while (true) {
            $action = select(
                label: 'What would you like to do?',
                options: [
                    'repositories' => 'Repositories',
                    'exit' => 'Exit',
                ],
                default: 'repositories',
            );

            if ($action === 'exit') {
                break;
            }

            if ($action === 'repositories') {
                $navigation = $this->repositories($github);

                if ($navigation === Navigation::Exit) {
                    break;
                }
            }
        }

        outro('Happy coding!');

        return Command::SUCCESS;
    }

    private function repositories(
        GitHubService $github,
    ): Navigation {
        while (true) {
            $action = select(
                label: 'Repositories',
                options: [
                    'create' => 'Create a repository',
                    'publish' => 'Publish an existing local project',
                    'back' => 'Back',
                ],
                default: 'create',
            );

            if ($action === 'back') {
                return Navigation::Back;
            }

            if (in_array($action, ['create', 'publish'], true)) {
                $navigation = (new CreateRepository($github))->run(
                    publishLocal: $action === 'publish',
                );

                if ($navigation === Navigation::Exit) {
                    return Navigation::Exit;
                }
            }
        }
    }
}
