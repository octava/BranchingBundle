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

    public function isEnabled()
    {
        return $this->config['enabled'];
    }

    /**
     * @return array
     */
    public function getConnections()
    {
        return $this->config['connection_urls'];
    }

    public function getIgnoreTables()
    {
        return $this->config['ignore_tables'];
    }

    public function getProjectDir()
    {
        return $this->config['project_dir'];
    }

    public function urlExists($url)
    {
        $result = in_array($url, $this->getConnections());

        return $result;
    }
}