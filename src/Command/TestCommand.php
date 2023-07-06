<?php

namespace SymfonyToolboxBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SymfonyToolboxBundle\SymfonyToolboxBundle;

#[AsCommand(
    name: 'app:test',
    description: 'test command',
)]
class TestCommand extends Command
{
    public function __construct(protected LoggerInterface $logger, private SymfonyToolboxBundle $bundle)
    {
        dd($bundle);
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $this->logger->info($this->bundle->getName());

        return 0;
    }
}
