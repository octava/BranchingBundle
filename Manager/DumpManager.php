<?php


namespace Octava\Bundle\BranchingBundle\Manager;


use Doctrine\DBAL\Connection;
use Octava\Bundle\BranchingBundle\Helper\MySqlDump;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class DumpManager
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function run(Connection $connection, $ignoreTables, $createIgnoreTablesEmpty, $filename, $dryRun)
    {
        $cmd = [];

        if ($createIgnoreTablesEmpty) {
            $args = MySqlDump::buildCreateDumpArgs(
                $connection,
                null,
                $createIgnoreTablesEmpty ? [] : $ignoreTables
            );
            $create = new Process($args);
            $create = $create->getCommandLine();
            $cmd[] = $create;
            $cmd[] = '|';
            $cmd[] = sprintf('gzip > "%s";', $filename);

            $args = MySqlDump::buildDataDumpArgs(
                $connection,
                null,
                $ignoreTables
            );
            $create = new Process($args);
            $create = $create->getCommandLine();
            $cmd[] = $create;
            $cmd[] = '|';
            $cmd[] = sprintf('gzip >> "%s";', $filename);
        } else {
            $args = MySqlDump::buildDataDumpArgs(
                $connection,
                null,
                $ignoreTables
            );
            $key = array_search('--no-create-info', $args);
            if (false !== $key) {
                unset($args[$key]);
            }
            $create = new Process($args);
            $create = $create->getCommandLine();
            $cmd[] = $create;
            $cmd[] = '|';
            $cmd[] = sprintf('gzip > "%s";', $filename);
        }

        $cmd = implode(' ', $cmd);

        $this->logger->info($cmd);

        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout(3600);

        if (!$dryRun) {
            $process->mustRun();
        }

        $this->logger->info(sprintf('File created "%s"', $filename));
    }
}