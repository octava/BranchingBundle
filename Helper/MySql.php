<?php


namespace Octava\Bundle\BranchingBundle\Helper;


use Doctrine\DBAL\Connection;

class MySql
{
    static public function buildConnectionArgs(Connection $connection, $database = null)
    {
        $result = ['mysql'];

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
        if (!$database) {
            $database = $connection->getDatabase();
        }
        $result[] = "--database=$database";

        return $result;
    }
}