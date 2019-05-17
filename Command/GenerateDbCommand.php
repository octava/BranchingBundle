<?php

namespace Octava\Bundle\BranchingBundle\Command;

use Monolog\Handler\StreamHandler;
use Octava\Bundle\BranchingBundle\Helper\Database;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GenerateDbCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected static $defaultName = 'octava:branching:generate-db';

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    protected function configure()
    {
        $this
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
        /** @var ConsoleOutput $output */
        $helper = $this->getContainer()->get(Database::class);
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
            $helper->generateDatabase($helper->getDbNameOriginal(), array_map('trim', $ignoreTables));

            $logger->debug('Database created successfully');
        } else {
            $logger->debug('Database already exist', [$branchDbName]);
        }
    }
}
