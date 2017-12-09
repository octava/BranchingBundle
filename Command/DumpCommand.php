<?php


namespace Octava\Bundle\BranchingBundle\Command;


use Doctrine\DBAL\Connection;
use Octava\Bundle\BranchingBundle\Manager\DumpManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('octava:branching:dump')
            ->setDescription('Create mysql dump file')
            ->addArgument('connection-name', InputArgument::REQUIRED, 'DBAL connection name.')
            ->addOption(
                'filename',
                'f',
                InputOption::VALUE_REQUIRED,
                'Dump dir. Default: %kernel.logs_dir%/<connection-name>.sql.tgz'
            )
            ->addOption(
                'ignore-tables',
                'i',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Ignore table list'
            )
            ->addOption(
                'create-ignore-tables-empty',
                null,
                InputOption::VALUE_REQUIRED,
                'Create ignore tables with no data'
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute commands as a dry run.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connectionId = sprintf('doctrine.dbal.default_connection', $input->getArgument('connection-name'));
        if (!$this->getContainer()->has($connectionId)) {
            throw new \RuntimeException(sprintf('Service connection "%s" not found', $connectionId));
        }

        /** @var Connection $connection */
        $connection = $this->getContainer()->get($connectionId);
        $filename = $input->getOption('filename');
        if (is_null($filename)) {
            $filename = sprintf(
                "%s/%s.sql.tgz",
                $this->getContainer()->getParameter('kernel.project_dir'),
                $connection->getDatabase()
            );
        }

        $this->getContainer()->get(DumpManager::class)
            ->run(
                $connection,
                $input->getOption('ignore-tables'),
                $input->getOption('create-ignore-tables-empty'),
                $filename,
                $input->getOption('dry-run')
            );
    }
}