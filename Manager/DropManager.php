<?php

namespace Octava\Bundle\BranchingBundle\Manager;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Octava\Bundle\BranchingBundle\Config\SwitchConfig;
use Octava\Bundle\BranchingBundle\Helper\Git;
use Psr\Log\LoggerInterface;


class DropManager
{
    private const PATTERN_BRANCH = '|_branch_(.+)$|ius';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SwitchConfig
     */
    private $config;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(LoggerInterface $logger, SwitchConfig $config, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->entityManager = $entityManager;
    }

    public function run($dryRun)
    {
        $branches = Git::getRemoteBranches($this->config->getProjectDir());
        $modifiedBranches = array_map(function ($value) {
            return str_replace('-', '_', strtolower($value));
        }, $branches);

        $databases = array_values($this->getProjectDatabases());

        $this->logger->info('Branch databases', $databases);

        foreach ($databases as $database) {
            $match = preg_match(self::PATTERN_BRANCH, $database, $matches);
            if ($match && !in_array($matches[1], $modifiedBranches, true)) {
                $this->logger->notice('Remove database', [
                    'database' => $database,
                    'branch' => $matches[1],
                    'dryRun' => $dryRun,
                ]);

                if (!$dryRun) {
                    $this->entityManager->getConnection()->getSchemaManager()
                        ->dropDatabase($database);
                }
            }
        }
    }

    protected function getProjectDatabases()
    {
        $result = $this->entityManager->getConnection()->getSchemaManager()->listDatabases();

        $originalDatabaseNames = $this->getOriginalDatabaseNames();
        $result = array_filter($result, function ($val) use ($originalDatabaseNames) {
            $isBranch = preg_match(self::PATTERN_BRANCH, $val);

            $exists = false;
            foreach ($originalDatabaseNames as $name) {
                if (0 === stripos($val, $name)) {
                    $exists = true;
                    break;
                }
            }

            return $isBranch && $exists;
        });

        return $result;
    }

    protected function getOriginalDatabaseNames()
    {
        $result = [];
        foreach ($this->config->getConnections() as $url) {
            $connection = DriverManager::getConnection(['driver' => 'pdo_mysql', 'url' => $url]);
            $result[] = $connection->getDatabase();
        }

        return $result;
    }
}
