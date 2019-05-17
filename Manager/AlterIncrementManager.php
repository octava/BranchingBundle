<?php


namespace Octava\Bundle\BranchingBundle\Manager;


use Doctrine\DBAL\Connection;
use Octava\Bundle\BranchingBundle\Config\AlterIncrementConfig;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AlterIncrementManager
{
    /**
     * @var AlterIncrementConfig
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * AlterIncrementManager constructor.
     * @param AlterIncrementConfig $config
     * @param RegistryInterface $doctrine
     * @param LoggerInterface $logger
     */
    public function __construct(
        AlterIncrementConfig $config,
        RegistryInterface $doctrine,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    /**
     * @param int $branchId
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    public function run($branchId, $environment, $dryRun)
    {
        foreach ($this->config->getMap() as $connectionName => $map) {
            /** @var Connection $connection */
            $connection = $this->doctrine->getConnection($connectionName);
            if (!$connection) {
                throw new \RuntimeException(sprintf('Invalid connection name "%s"', $connectionName));
            }

            foreach ($map as $tableName => $item) {
                if (false !== strpos($tableName, ':')) {
                    $em = $this->doctrine->getEntityManager($connectionName);
                    $tableName = $em->getClassMetadata($tableName)->getTableName();
                }

                if (!array_key_exists($environment, $item)) {
                    throw new \RuntimeException(
                        sprintf('Invalid configuration environment "%s" not found', $environment)
                    );
                }

                $currentId = $this->getCurrentId($connection, $tableName);
                $calculatedId = $this->calculateIncrement($branchId, $item[$environment]);

                if ($currentId && $currentId >= $calculatedId) {
                    $this->logger->info(
                        sprintf(
                            'Nothing change. Current Id (%d) more or equal than %s calculated (%s)',
                            $currentId,
                            $tableName,
                            $calculatedId
                        )
                    );
                } else {
                    $this->alterTableIncrement($connection, $tableName, $calculatedId, $dryRun);
                }
            }
        }
    }

    /**
     * @param Connection $connection
     * @param $tableName
     * @return bool|string
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getCurrentId(Connection $connection, $tableName)
    {
        $query = 'SELECT AUTO_INCREMENT as id FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?';
        $statement = $connection->executeQuery($query, [$connection->getDatabase(), $tableName]);
        $result = $statement->fetchColumn();
        $result = $result ? (int)$result : $result;

        return $result;
    }

    private function calculateIncrement($branchId, array $params)
    {
        $result = $params['start'] + $branchId * $params['step'];

        return $result;
    }

    /**
     * @param Connection $connection
     * @param $tableName
     * @param $calculatedId
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    private function alterTableIncrement(Connection $connection, $tableName, $calculatedId, $dryRun)
    {
        $query = sprintf('ALTER TABLE `%s` AUTO_INCREMENT = ?', $tableName);
        if (!$dryRun) {
            $connection->executeUpdate($query, [$calculatedId], [\PDO::PARAM_INT]);
            $this->logger->info('Increment updated', [$query, $tableName, $calculatedId]);
        } else {
            $this->logger->info('Query skipped', [$query, $tableName, $calculatedId]);
        }
    }
}
