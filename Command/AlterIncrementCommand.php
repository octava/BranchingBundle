<?php

namespace Octava\Bundle\BranchingBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOStatement;
use Octava\Bundle\BranchingBundle\Config\AlterIncrementConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AlterIncrementCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected static $defaultName = 'octava:branching:alter-increment';

    protected function configure()
    {
        $this
            ->setDescription('Смещаем автоинкременты в табличках, которые используются для генерации transactionId');
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $msg = [];

        $branchId = $this->getBranchId();
        $env = $this->getContainer()->get('kernel')->getEnvironment();
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $map = $this->getContainer()->get(AlterIncrementConfig::class)->getMap();
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
                    $connection = $this->getContainer()->get('doctrine')->getConnection();
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
        $rootPath = $this->getContainer()->getParameter('kernel.root_dir');
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
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $baseName = $connection->getDatabase();
        /** @var PDOStatement $statement */
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
