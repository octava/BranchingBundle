<?php
namespace Octava\Bundle\BranchingBundle\Helper;

use Symfony\Component\Process\ProcessBuilder;

class MySqlDump
{
    static public function makeDumpCommand($host, $port, $user, $password, $dbName)
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

        $result = $builder->getProcess()->getCommandLine();
        return $result;
    }
}
