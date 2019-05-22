<?php

namespace Octava\Bundle\BranchingBundle\Manager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Octava\Bundle\BranchingBundle\Config\SwitchConfig;
use Octava\Bundle\BranchingBundle\Helper\Git;
use Octava\Bundle\BranchingBundle\Helper\MySql;
use Octava\Bundle\BranchingBundle\Helper\MySqlDump;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class DatabaseManager
{
    /**
     * @var SwitchConfig
     */
    private $switchConfig;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(SwitchConfig $switchConfig, LoggerInterface $logger)
    {
        $this->switchConfig = $switchConfig;
        $this->logger = $logger;
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function generateDatabases()
    {
        $result = [];

        if ($this->switchConfig->isEnabled()) {
            foreach ($this->switchConfig->getConnections() as $name => $url) {
                $connection = $this->getConnection($url);
                $originalDbName = $connection->getDatabase();
                $branchDbName = $this->generateBranchDatabaseName($originalDbName);

                if ($originalDbName != $branchDbName) {
                    if (!$this->databaseExists($branchDbName, $connection)) {
                        $this->generateDatabase(
                            $connection,
                            $originalDbName,
                            $branchDbName,
                            $this->switchConfig->getIgnoreTables()
                        );
                    }
                    $result[$name] = $branchDbName;
                }
            }
        }

        return $result;
    }

    /**
     * @param array $params
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateParams(array &$params)
    {
        if (array_key_exists('url', $params) && $this->switchConfig->isEnabled()) {
            $this->generateDatabases();

            $url = $params['url'];
            if ($this->switchConfig->urlExists($url)) {
                $connection = DriverManager::getConnection($params);
                $params = $connection->getParams();
                unset($params['url']);
                $params['dbname'] = $this->generateBranchDatabaseName($params['dbname']);
            }
        }
    }

    /**
     * @param $originalDbName
     * @return string
     */
    protected function generateBranchDatabaseName($originalDbName)
    {
        $branchRef = Git::getCurrentBranch($this->switchConfig->getProjectDir());

        $result = $originalDbName;
        $branchName = $this->prepareBranchName($branchRef);
        if (0 !== strpos(strrev($result), strrev($branchName))
            && 'master' != $branchName
        ) {
            $result = $result . '_branch_' . $branchName;
        }

        return $result;
    }

    /**
     * @param $url
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getOriginalDatabaseName($url)
    {
        return $this->getConnection($url)->getDatabase();
    }

    /**
     * @param $url
     * @return \Doctrine\DBAL\Connection
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getConnection($url)
    {
        return DriverManager::getConnection(['driver' => 'pdo_mysql', 'url' => $url]);
    }

    /**
     * @param $branchName
     *
     * @return bool|mixed|string
     */
    protected function prepareBranchName($branchName)
    {
        $result = str_replace('-', '_', strtolower($branchName));
        $pos = strrpos($branchName, '/');
        if (false !== $pos) {
            $result = substr($result, $pos + 1);
        }

        return $result;
    }

    protected function databaseExists($branchDbName, Connection $connection)
    {
        $result = in_array($branchDbName, $connection->getSchemaManager()->listDatabases());

        return $result;
    }

    /**
     * @param $cmd
     */
    protected function runCmd($cmd)
    {
        $process = new Process(
            $cmd,
            null,
            null,
            null,
            3600
        );
        $this->logger->debug('run process', ['cmd' => $process->getCommandLine()]);
        $process->mustRun();
    }

    private function generateDatabase(Connection $connection, $database, $branchDatabase, $ignoreTables)
    {
        if (!$this->databaseExists($branchDatabase, $connection)) {
            $connection->getSchemaManager()->createDatabase($branchDatabase);

            try {
                $mysql = new Process(MySql::buildConnectionArgs($connection, $branchDatabase));
                $mysqlCreate = new Process(MySqlDump::buildCreateDumpArgs($connection, $database));

                $cmd = [];
                $cmd[] = $mysqlCreate->getCommandLine();
                $cmd[] = 'sed -e \'s/DEFINER[ ]*=[ ]*[^*]*\*/\*/\'';
                $cmd[] = 'sed -e \'s/DEFINER[ ]*=[ ]*[^*]*PROCEDURE/PROCEDURE/\'';
                $cmd[] = 'sed -e \'s/DEFINER[ ]*=[ ]*[^*]*FUNCTION/FUNCTION/\'';
                $cmd[] = 'sed -e \'s/`' . $database . '`\./`' . $branchDatabase . '`\./\'';

                $cmd[] = $mysql->getCommandLine();
                $cmd = implode(' | ', $cmd);
                $this->runCmd($cmd);

                $mysqlData = new Process(MySqlDump::buildDataDumpArgs($connection, $database, $ignoreTables));
                $cmd = $mysqlData->getCommandLine() . ' | ' . $mysql->getCommandLine();
                $this->runCmd($cmd);
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
                $connection->getSchemaManager()->dropDatabase($branchDatabase);
                throw  $exception;
            }
        }
    }
}
