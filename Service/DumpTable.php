<?php
namespace Octava\Bundle\BranchingBundle\Service;

use Doctrine\ORM\EntityManager;
use Octava\Bundle\BranchingBundle\Helper\MySqlDump;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Process\Process;

class DumpTable
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function makeBeginDumpCommand()
    {
        $connectionParams = $this->getEntityManager()->getConnection()->getParams();
        $result = MySqlDump::makeDataDumpCommand(
            $connectionParams['host'],
            $connectionParams['port'],
            $connectionParams['user'],
            $connectionParams['password'],
            $connectionParams['dbname']
        );

        return $result;
    }

    public function makeCreateDumpCommand()
    {
        $connectionParams = $this->getEntityManager()->getConnection()->getParams();
        $result = MySqlDump::makeCreateDumpCommand(
            $connectionParams['host'],
            $connectionParams['port'],
            $connectionParams['user'],
            $connectionParams['password'],
            $connectionParams['dbname']
        );

        return $result;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @param EntityManager $entityManager
     *
     * @return self
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;

        return $this;
    }

    public function generateSql($entityName)
    {
        $tableName = $this->getEntityManager()->getClassMetadata($entityName)->getTableName();
        $result = [];
        $result = array_merge($result, [$this->createDump($tableName)]);
        $result = array_merge($result, [$this->dumpTable($tableName)]);
        $result = array_merge($result, $this->clearExtTranslations($entityName));
        $result = array_merge($result, [$this->dumpExtTranslations($entityName)]);

        return $result;
    }

    protected function createDump($tableName)
    {
        $cmd = $this->makeCreateDumpCommand();
        $cmd .= '  '.$tableName;
        $result = $this->mustRun($cmd);

        return $result;
    }

    protected function dumpTable($tableName)
    {
        $cmd = $this->makeBeginDumpCommand();
        $cmd .= '  --extended-insert --lock-tables --quick '.$tableName;
        $result = $this->mustRun($cmd);

        return $result;
    }

    /**
     * @param $entityName
     *
     * @return array
     */
    protected function clearExtTranslations($entityName)
    {
        $entityManager = $this->getEntityManager();
        $className = $entityManager->getClassMetadata($entityName)->getReflectionClass()->getName();
        $extTranslationTableName = $entityManager
            ->getClassMetadata('Gedmo\Translatable\Entity\Translation')
            ->getTableName();
        $connection = $entityManager->getConnection();
        $delete = [];
        $delete[] = <<<SQL
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SQL;
        $delete[] = sprintf(
            'DELETE FROM %s WHERE object_class = %s;',
            $extTranslationTableName,
            $connection->quote($className)
        );
        $delete[] = <<<SQL
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
SQL;

        return $delete;
    }

    protected function dumpExtTranslations($entityName)
    {
        $entityManager = $this->getEntityManager();
        $className = $entityManager->getClassMetadata($entityName)->getReflectionClass()->getName();
        $extTranslationTableName = $entityManager
            ->getClassMetadata('Gedmo\Translatable\Entity\Translation')
            ->getTableName();
        $connection = $entityManager->getConnection();

        $cmd = $this->makeBeginDumpCommand();
        $cmd .= ' --extended-insert --lock-tables --quick --skip-add-drop-table --no-create-info';
        $cmd .= sprintf(
            ' --tables %s --where="object_class = %s"',
            $extTranslationTableName,
            $connection->quote(addslashes($className))
        );

        $insert = $this->mustRun($cmd);
        $result = preg_replace(
            '/(?<!rgb|rgba|hsl|hsla|rect)\(\d+,/',
            '(NULL,',
            $insert
        );

        return $result;
    }

    protected function mustRun($cmd)
    {
        $this->getLogger()->debug('Process', ['cmd' => $cmd]);
        $process = new Process($cmd);
        $process->mustRun();

        return $process->getOutput();
    }
}
