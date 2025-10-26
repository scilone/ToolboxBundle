<?php

namespace SciloneToolboxBundle\Symfony\Command;

use SciloneToolboxBundle\Elasticsearch\FixtureManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'scilone:elasticsearch:load-fixtures',
    description: 'Load Elasticsearch fixtures from YAML files',
    hidden: false
)]
class LoadFixturesCommand extends Command
{
    public function __construct(private readonly FixtureManager $fixtureManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Reset the loading of fixtures')
            ->addOption('no-safety', null, InputOption::VALUE_NONE, 'Disable safety: allow loading fixtures on non-local Elasticsearch hosts');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $reset = (bool) $input->getOption('reset');
            $noSafety = (bool) $input->getOption('no-safety');

            if ($noSafety) {
                $io->warning('Safety checks are disabled with --no-safety. Make sure you know what you are doing.');
            }

            $this->fixtureManager->loadFixtures($reset, $noSafety);
            $io->success('Fixtures loaded successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to load fixtures: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
