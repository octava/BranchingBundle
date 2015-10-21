<?php
namespace Octava\Bundle\BranchingBundle\Config;

class AlterIncrementConfig
{
    protected $map = [];

    public function __construct(array $map = [])
    {
        $this->map = $map;
    }

    /**
     * @return array
     */
    public function getMap()
    {
        return $this->map;
    }
}
