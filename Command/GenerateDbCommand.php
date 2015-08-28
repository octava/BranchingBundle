<?php
namespace Octava\Bundle\BranchingBundle\Command;

use Monolog\Handler\StreamHandler;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateDbCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('octava:branching:generate-db')
            ->setDescription('Detect current branch and create db')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'If you want copy db by specific name'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Symfony\Component\Console\Output\ConsoleOutput $output */
        $helper = $this->getContainer()->get('octava_branching.helper.database');
        $logger = $helper->getLogger();
        if ($output->isDebug()) {
            $logger->pushHandler(new StreamHandler(STDOUT));
        }

        $name = $input->getArgument('name');
        if ($name) {
            $branchDbName = $name;
        } else {
            $branchDbName = $helper->generateDatabaseName();
        }

        $logger->debug('Run command', ['command' => $this->getName(), 'db name' => $branchDbName]);

        if (!$helper->databaseExists($branchDbName)) {
            $helper->generateDatabase($branchDbName);

            $logger->debug('Create complete');
        } else {
            $logger->debug('Database already exist');
        }
    }
}
