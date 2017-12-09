<?php


namespace Octava\Bundle\BranchingBundle\Command;


use Octava\Bundle\BranchingBundle\Helper\Git;
use Octava\Bundle\BranchingBundle\Manager\AlterIncrementManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AlterIncrementCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('octava:branching:alter-increment')
            ->setDescription('Alter increment id for tables from config')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute commands as a dry run.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Doctrine\DBAL\DBALException
     */
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
            $this->getContainer()->get(AlterIncrementManager::class)
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