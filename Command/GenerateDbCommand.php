<?php
namespace Octava\Bundle\BranchingBundle\Command;

use Monolog\Handler\StreamHandler;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateDbCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('octava:branching:generate-db')
            ->addOption(
                'ignore-table-data',
                'i',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Do not dump the specified table data.'
            )
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
        $ignoreTables = $this->getContainer()
            ->get('octava_branching.config.ignore_tables')
            ->getIgnoreTables();
        if (!empty($input->getOption('ignore-table-data'))) {
            $ignoreTables = $input->getOption('ignore-table-data');
        }

        $logger->debug(
            'Run command',
            ['command' => $this->getName(), 'db_name' => $branchDbName, 'ignore_tables' => $ignoreTables]
        );

        if (!$helper->databaseExists($branchDbName)) {
            $helper->generateDatabase($branchDbName, array_map('trim', $ignoreTables));

            $logger->debug('Database created successfully');
        } else {
            $logger->debug('Database already exist', [$branchDbName]);
        }
    }
}
