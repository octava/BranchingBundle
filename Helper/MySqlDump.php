<?php

namespace Octava\Bundle\BranchingBundle\Helper;

use Symfony\Component\Process\Process;

class MySqlDump
{
    static public function makeCreateDumpCommand($host, $port, $user, $password, $dbName)
    {
        $command = self::buildDumpCommand($host, $port, $user, $password, $dbName);
        $command[] = '--no-data';
        $command[] = '--skip-lock-tables';
        $command[] = '--skip-add-locks';
        $command[] = '--routines';

        $process = new Process($command);
        $result = $process->getCommandLine();

        return $result;
    }

    static public function makeDataDumpCommand($host, $port, $user, $password, $dbName, array $ignoreTables = [])
    {
        $command = self::buildDumpCommand($host, $port, $user, $password, $dbName);
        $command[] = '--no-create-info';
        $command[] = '--skip-lock-tables';
        $command[] = '--skip-add-locks';
        $command[] = '--extended-insert';

        $ignoreTables = array_filter($ignoreTables);
        $ignoreTables = array_unique($ignoreTables);
        $ignoreTables = array_map('trim', $ignoreTables);
        foreach ($ignoreTables as $rule) {
            $command[] = sprintf('--ignore-table=%s.%s', $dbName, $rule);
        }

        $process = new Process($command);
        $result = $process->getCommandLine();

        return $result;
    }

    static public function makeDumpCommand($host, $port, $user, $password, $dbName)
    {
        $command = self::buildDumpCommand($host, $port, $user, $password, $dbName);

        $process = new Process($command);
        $result = $process->getCommandLine();

        return $result;
    }

    public static function buildDumpCommand($host, $port, $user, $password, $dbName): array
    {
        $command = ['mysqldump'];
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
        if ($dbName) {
            $command[] = $dbName;
        }

        return $command;
    }
}
