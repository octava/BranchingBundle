<?php

namespace Octava\Bundle\BranchingBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Octava\Bundle\BranchingBundle\Config\AlterIncrementConfig;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AlterIncrementCommand extends Command
{
    const NAME = 'octava:branching:alter-increment';

    /**
     * @var string
     */
    protected $kernelRoot;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var AlterIncrementConfig
     */
    protected $alterConfig;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * AlterIncrementCommand constructor.
     * @param string $kernelRoot
     * @param string $environment
     * @param AlterIncrementConfig $alterConfig
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        string $kernelRoot,
        string $environment,
        AlterIncrementConfig $alterConfig,
        EntityManagerInterface $entityManager
    ) {
        $this->kernelRoot = $kernelRoot;
        $this->environment = $environment;
        $this->alterConfig = $alterConfig;
        $this->entityManager = $entityManager;

        parent::__construct(self::NAME);
    }

    protected function configure()
    {
        $this
            ->setDescription('Смещаем автоинкременты в табличках, которые используются для генерации transactionId');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $msg = [];

        $branchId = $this->getBranchId();
        $env = $this->environment;
        $entityManager = $this->entityManager;

        $map = $this->alterConfig->getMap();
        foreach ($map as $className => $item) {
            $repository = $entityManager->getRepository($className);
            $tableName = $entityManager->getClassMetadata($className)->getTableName();
            if ($repository && isset($item[$env])) {
                $currentId = $this->getCurrentId($tableName);
                $calculatedId = $this->calculateIncrement($branchId, $item[$env]);
                if ($currentId >= $calculatedId) {
                    $msg[] = sprintf(
                        '<info>Nothing change. Current Id (%d) more or equal than %s calculated (%s)</info>',
                        $currentId,
                        $tableName,
                        $calculatedId
                    );
                } else {
                    $connection = $entityManager->getConnection();
                    $schemaManager = $connection->getSchemaManager();
                    if ($schemaManager->tablesExist([$tableName]) === true) {
                        $query = sprintf('ALTER TABLE `%s` AUTO_INCREMENT = %d', $tableName, $calculatedId);
                        $connection->exec($query);

                        $msg[] = $query;
                    }
                }
            }
        }

        foreach ($msg as $item) {
            $output->writeln($item);
        }
    }

    protected function getBranchId()
    {
        $rootPath = $this->kernelRoot;
        $cmd = 'cd ' . $rootPath . ' && git symbolic-ref HEAD';
        $branchName = exec($cmd);

        $pos = strrpos($branchName, '/');
        if (false !== $pos) {
            $branchName = substr($branchName, $pos + 1);
        }
        $pos = strrpos($branchName, '-');
        if (false !== $pos) {
            $branchName = substr($branchName, $pos + 1);
        }

        $result = 0;
        if (is_numeric($branchName)) {
            $result = (int)$branchName;
        }

        return $result;
    }

    protected function getCurrentId($tableName)
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->entityManager->getConnection();
        $baseName = $connection->getDatabase();
        /** @var \Doctrine\DBAL\Driver\PDOStatement $statement */
        $statement = $connection->query(
            sprintf(
                'SELECT `AUTO_INCREMENT` as id FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
                $connection->quote($baseName),
                $connection->quote($tableName)
            )
        );
        $result = $statement->fetchAll();
        if (!$result) {
            $result = 0;
        } else {
            $result = (int)$result[0]['id'];
        }

        return $result;
    }

    protected function calculateIncrement($branchId, array $params)
    {
        $result = $params['start'] + $branchId * $params['step'];

        return $result;
    }
}
