<?php

namespace Octava\Bundle\BranchingBundle\Command;

use Doctrine\DBAL\Connection;
use Octava\Bundle\BranchingBundle\Manager\LoadManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected static $defaultName = 'octava:branching:load';

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
            ->setDescription('Load mysql dump file which was created by dump command')
            ->addArgument('connection-name', InputArgument::REQUIRED, 'DBAL connection name.')
            ->addOption(
                'filename',
                'f',
                InputOption::VALUE_REQUIRED,
                'Dump dir. Default: %kernel.logs_dir%/branching/<connection-name>.sql.tgz'
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
        $this->getContainer()->get(LoadManager::class)
            ->run(
                $connection,
                $input->getOption('filename'),
                $input->getOption('dry-run')
            );
    }
}