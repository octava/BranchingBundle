<?php

namespace Octava\Bundle\BranchingBundle\Command;

use Octava\Bundle\BranchingBundle\Manager\DropManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DropCommand extends Command
{
    protected static $defaultName = 'octava:branching:drop';

    /**
     * @var DropManager
     */
    protected $dropManager;

    /**
     * DropCommand constructor.
     * @param DropManager $dropManager
     */
    public function __construct(DropManager $dropManager)
    {
        $this->dropManager = $dropManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Drop databases which does not have existing branch')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute commands as a dry run.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->dropManager
            ->run($input->getOption('dry-run'));
    }
}