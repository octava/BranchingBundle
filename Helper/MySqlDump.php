<?php
namespace Octava\Bundle\BranchingBundle\Helper;

use Symfony\Component\Process\ProcessBuilder;

class MySqlDump
{
    static public function makeCreateDumpCommand($host, $port, $user, $password, $dbName)
    {
        $builder = self::createDumpBuilder($host, $port, $user, $password, $dbName);
        $builder->add('--no-data');

        $result = $builder->getProcess()->getCommandLine();

        return $result;
    }

    static public function makeDataDumpCommand($host, $port, $user, $password, $dbName, array $ignoreTables = [])
    {
        $builder = self::createDumpBuilder($host, $port, $user, $password, $dbName);
        $builder->add('--no-create-info');
        $builder->add('--lock-tables');
        $builder->add('--extended-insert');
        $builder->add('--quick');

        $ignoreTables = array_filter($ignoreTables);
        $ignoreTables = array_unique($ignoreTables);
        $ignoreTables = array_map('trim', $ignoreTables);
        foreach ($ignoreTables as $rule) {
            $builder->add(sprintf('--ignore-table=%s.%s', $dbName, $rule));
        }

        $result = $builder->getProcess()->getCommandLine();

        return $result;
    }

    static public function makeDumpCommand($host, $port, $user, $password, $dbName)
    {
        $builder = self::createDumpBuilder($host, $port, $user, $password, $dbName);

        $result = $builder->getProcess()->getCommandLine();

        return $result;
    }

    public static function createDumpBuilder($host, $port, $user, $password, $dbName)
    {
        $builder = new ProcessBuilder();
        $builder->setPrefix('mysqldump');
        if ($host) {
            $builder->add("--host=$host");
        }
        if ($port) {
            $builder->add("--port=$port");
        }
        if ($user) {
            $builder->add("--user=$user");
        }
        if ($password) {
            $builder->add("--password=$password");
        }
        if ($dbName) {
            $builder->add($dbName);
        }

        return $builder;
    }
}
