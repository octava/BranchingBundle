<?php
namespace Octava\Bundle\BranchingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class DropCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('octava:branching:drop')
            ->setDescription('Drop databases which does not have existing branch');
    }
}