<?php

namespace Octava\Bundle\BranchingBundle\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Monolog\Handler\NullHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Process\Process;

/**
 * Class Database
 *
 * @package Octava\Bundle\BranchingBundle\Helper
 */
class Database
{
    /**
     * @var
     */
    protected $tmpConnection;

    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var bool
     */
    protected $copyDbData;

    /**
     * @var string
     */
    protected $driver;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $port;

    /**
     * @var string
     */
    protected $user;
    /**
     * @var string
     */
    protected $password;
    /**
     * @var string
     */
    protected $dbName;
    /**
     * @var string
     */
    protected $dbNameOriginal;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Database constructor.
     *
     * @param $rootDir
     * @param $copyDbData
     * @param $driver
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     * @param $dbName
     * @param $dbNameOriginal
     */
    public function __construct(
        $rootDir,
        $copyDbData,
        $driver,
        $host,
        $port,
        $user,
        $password,
        $dbName,
        $dbNameOriginal
    ) {
        $this->rootDir = $rootDir;
        $this->copyDbData = (bool)$copyDbData;
        $this->driver = $driver;
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->dbName = $dbName;
        $this->dbNameOriginal = $dbNameOriginal;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new Logger(__CLASS__, [new NullHandler()]);
        }

        return $this->logger;
    }

    /**
     * @param Logger $logger
     *
     * @return self
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return string
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @return string
     */
    public function getDbNameOriginal()
    {
        return $this->dbNameOriginal;
    }

    /**
     * @return string
     */
    public function getRootDir()
    {
        return $this->rootDir;
    }

    /**
     * @return boolean
     */
    public function isCopyDbData()
    {
        return $this->copyDbData;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * @return string
     */
    public function generateDatabaseName()
    {
        $branchRef = Git::getCurrentBranch($this->getRootDir());

        $result = $this->getDbName();
        $branchName = $this->prepareBranchName($branchRef);
        if (0 !== strpos(strrev($result), strrev($branchName))
            && 'master' != $branchName
        ) {
            $result = $result . '_branch_' . $branchName;
        }

        return $result;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function databaseExists($name)
    {
        if (in_array($name, $this->getTmpConnection()->getSchemaManager()->listDatabases())) {
            return true;
        }

        return false;
    }

    /**
     * @param string $dstDbName
     * @param array $ignoreTables
     */
    public function generateDatabase($dstDbName, array $ignoreTables = [])
    {
        $connection = $this->getTmpConnection();
        $connection->getSchemaManager()->createDatabase($dstDbName);

        if ($this->isCopyDbData()) {
            $host = $this->getHost();
            $port = $this->getPort();
            $user = $this->getUser();
            $password = $this->getPassword();
            $srcDbName = $this->getDbName();
            $mysql = $this->makeMysqlCommand($dstDbName, $host, $port, $user, $password);

            $cmd = MySqlDump::makeCreateDumpCommand(
                $host,
                $port,
                $user,
                $password,
                $srcDbName
            );
            $cmd = $cmd . " | " . $mysql;
            $this->runCmd($cmd);

            $cmd = MySqlDump::makeDataDumpCommand(
                $host,
                $port,
                $user,
                $password,
                $srcDbName,
                $ignoreTables
            );
            $mysql = $this->makeMysqlCommand($dstDbName, $host, $port, $user, $password);
            $cmd = $cmd . " | " . $mysql;

            $this->runCmd($cmd);
        }
    }

    /**
     * @param $branchName
     *
     * @return mixed
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

    /**
     * @return Connection
     * @throws DBALException
     */
    protected function getTmpConnection()
    {
        if (!$this->tmpConnection) {
            $params = [
                'driver' => $this->getDriver(),
                'host' => $this->getHost(),
                'port' => $this->getPort(),
                'dbname' => $this->getDbNameOriginal(),
                'user' => $this->getUser(),
                'password' => $this->getPassword(),
            ];
            $this->tmpConnection = DriverManager::getConnection($params);
        }

        return $this->tmpConnection;
    }

    /**
     * @param $dstDbName
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     *
     * @return string
     */
    protected function makeMysqlCommand($dstDbName, $host, $port, $user, $password)
    {
        $command = ['mysql'];
        if ($host) {
            $command[] = "--host=$host";
        }
        if ($host) {
            $command[] = "--host=$host";
        }
        if ($port) {
            $command[] = "--port=$port";
        }
        if ($user) {
            $command[] = "--user=$user";
        }
        if ($password) {
            $command[] = "--password=$password";
        }
        $command[] = "--database=$dstDbName";

        $process = new Process($command);
        return $process->getCommandLine();
    }

    /**
     * @param $cmd
     */
    protected function runCmd($cmd)
    {
        $this->getLogger()->debug('Command', [$cmd]);
        $process = new Process(
            $cmd,
            null,
            null,
            null,
            null
        );
        $process->mustRun();
    }
}
