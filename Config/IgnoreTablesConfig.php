<?php

namespace Octava\Bundle\BranchingBundle\Config;

class IgnoreTablesConfig
{
    protected $ignoreTables = [];

    public function __construct(array $ignoreTables = [])
    {
        $this->ignoreTables = $ignoreTables;
    }

    /**
     * @return array
     */
    public function getIgnoreTables()
    {
        return $this->ignoreTables;
    }
}
