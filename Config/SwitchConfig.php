<?php


namespace Octava\Bundle\BranchingBundle\Config;


class SwitchConfig
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
}