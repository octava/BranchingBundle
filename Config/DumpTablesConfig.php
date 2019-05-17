<?php

namespace Octava\Bundle\BranchingBundle\Config;

class DumpTablesConfig
{
    protected $repositories = [];

    public function __construct(array $repositories = [])
    {
        $this->repositories = $repositories;
    }

    /**
     * @return array
     */
    public function getRepositories()
    {
        return $this->repositories;
    }
}
