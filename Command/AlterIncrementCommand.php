<?php

namespace Octava\Bundle\BranchingBundle\Command;

use Octava\Bundle\BranchingBundle\Helper\Git;
use Octava\Bundle\BranchingBundle\Manager\AlterIncrementManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AlterIncrementCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected static $defaultName = 'octava:branching:alter-increment';

    protected function configure()
    {
        $this
            ->setDescription('Alter increment id for tables from config')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute commands as a dry run.');
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $branchName = Git::getCurrentBranch($this->getContainer()->getParameter('kernel.project_dir'));
        $pos = strrpos($branchName, '-');
        $branchId = 0;
        if (false !== $pos) {
            $branchId = substr($branchName, $pos + 1);
            $branchId = is_numeric($branchId) ? (int)$branchId : 0;
        }

        if ($branchId) {
            $this->getContainer()
                ->get(AlterIncrementManager::class)
                ->run(
                    $branchId,
                    $this->getContainer()->getParameter('kernel.environment'),
                    $input->getOption('dry-run')
                );
        } else {
            $output->writeln(
                sprintf('Skipped, because invalid branch name "%s"', $branchName)
            );
        }
    }
}
