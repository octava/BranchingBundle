<?php


namespace Octava\Bundle\BranchingBundle;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory as BaseConnectionFactory;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Octava\Bundle\BranchingBundle\Manager\DatabaseManager;

class ConnectionFactory extends BaseConnectionFactory
{
    /**
     * @var DatabaseManager
     */
    protected $databaseManager;

    /**
     * @param DatabaseManager $databaseManager
     * @return ConnectionFactory
     */
    public function setDatabaseManager(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;

        return $this;
    }

    /**
     * @param array $params
     * @param Configuration|null $config
     * @param EventManager|null $eventManager
     * @param array $mappingTypes
     * @return \Doctrine\DBAL\Connection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createConnection(
        array $params,
        Configuration $config = null,
        EventManager $eventManager = null,
        array $mappingTypes = []
    ) {
        $this->databaseManager->updateParams($params);
        $result = parent::createConnection($params, $config, $eventManager,
            $mappingTypes);

        return $result;
    }
}
