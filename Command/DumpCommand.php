<?php

namespace Octava\Bundle\BranchingBundle\Command;

use Doctrine\DBAL\Connection;
use Octava\Bundle\BranchingBundle\Manager\DumpManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DumpCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected static $defaultName = 'octava:branching:dump';

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
            ->setDescription('Create mysql dump file')
            ->addArgument('connection-name', InputArgument::REQUIRED, 'DBAL connection name.')
            ->addOption(
                'filename',
                'f',
                InputOption::VALUE_REQUIRED,
                'Dump dir. Default: %kernel.logs_dir%/<connection-name>.sql.tgz'
            )
            ->addOption(
                'ignore-table',
                'i',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Ignore table list'
            )
            ->addOption(
                'create-ignore-table-empty',
                null,
                InputOption::VALUE_NONE,
                'Create ignore tables with no data'
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute commands as a dry run.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connectionId = sprintf('doctrine.dbal.%s_connection', $input->getArgument('connection-name'));
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
                $input->getOption('ignore-table'),
                $input->getOption('create-ignore-table-empty'),
                $filename,
                $input->getOption('dry-run')
            );
    }
}
