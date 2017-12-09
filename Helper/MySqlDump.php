<?php

namespace Octava\Bundle\BranchingBundle\Helper;


use Doctrine\DBAL\Connection;

class MySqlDump
{
    static public function buildCreateDumpArgs(Connection $connection, $database = null, array $ignoreTables = [])
    {
        if (!$database) {
            $database = $connection->getDatabase();
        }

        $args = self::buildConnectionDumpArgs($connection, $database);
        $args = array_merge(
            $args,
            [
                '--no-data',
            ]
        );

        $args = array_merge($args, self::buildIgnoreTablesArgs($database, $ignoreTables));

        return $args;
    }

    static public function buildDataDumpArgs(Connection $connection, $database = null, array $ignoreTables = [])
    {
        if (!$database) {
            $database = $connection->getDatabase();
        }

        $args = self::buildConnectionDumpArgs($connection, $database);
        $args = array_merge(
            $args,
            [
                '--no-create-info',
                '--extended-insert',
            ]
        );

        $args = array_merge($args, self::buildIgnoreTablesArgs($database, $ignoreTables));

        return $args;
    }

    public static function buildConnectionDumpArgs(Connection $connection, $database)
    {
        $result = [
            'mysqldump'
        ];
        if ($host = $connection->getHost()) {
            $result[] = "--host=$host";
        }
        if ($port = $connection->getPort()) {
            $result[] = "--port=$port";
        }
        if ($user = $connection->getUsername()) {
            $result[] = "--user=$user";
        }
        if ($password = $connection->getPassword()) {
            $result[] = "--password=$password";
        }

        $result[] = $database;

        $result[] = '--skip-lock-tables';
        $result[] = '--skip-add-locks';

        return $result;
    }

    protected static function buildIgnoreTablesArgs($database, $ignoreTables)
    {
        $args = [];
        $ignoreTables = array_filter($ignoreTables);
        $ignoreTables = array_unique($ignoreTables);
        $ignoreTables = array_map('trim', $ignoreTables);
        foreach ($ignoreTables as $rule) {
            $args[] = sprintf('--ignore-table=%s.%s', $database, $rule);
        }

        return $args;
    }
}
