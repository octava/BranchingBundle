<?php
namespace Octava\Bundle\BranchingBundle\Helper;

use Doctrine\DBAL\DriverManager;
use Monolog\Handler\NullHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Process\Process;

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
     * @param LoggerInterface $logger
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

    public function generateDatabaseName()
    {
        $branchRef = Git::getCurrentBranch($this->getRootDir());

        $result = $this->getDbName();
        $branchName = $this->prepareBranchName($branchRef);
        if ('master' != $branchName) {
            $result = $result . '_branch_' . $branchName;
        }
        return $result;
    }

    public function databaseExists($name)
    {
        if (in_array($name, $this->getTmpConnection()->getSchemaManager()->listDatabases())) {
            return true;
        }
        return false;
    }

    public function generateDatabase($dstDbName)
    {
        $connection = $this->getTmpConnection();
        $connection->getSchemaManager()->createDatabase($dstDbName);

        if ($this->isCopyDbData()) {
            $host = $this->getHost();
            $port = $this->getPort() ? '-P' . $this->getPort() : '';
            $user = $this->getUser();
            $password = $this->getPassword() ? '-p' . $this->getPassword() : '';
            $srcDbName = $this->getDbName();

            $cmd = "mysqldump -h{$host} {$port} -u{$user} $password {$srcDbName}" .
                " | mysql -h{$host} ${port} -u{$user} {$password} {$dstDbName}";

            $process = new Process(
                $cmd,
                null,
                null,
                null,
                60 * 15
            );
            $process->mustRun();
        }
    }

    protected function prepareBranchName($branchName)
    {
        $result = str_replace('-', '_', strtolower($branchName));
        $pos = strrpos($branchName, '/');
        if (false !== $pos) {
            $result = substr($result, $pos + 1);
        }
        return $result;
    }

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
}
