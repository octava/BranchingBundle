<?php


namespace Octava\Bundle\BranchingBundle\Manager;


use Doctrine\DBAL\Connection;
use Octava\Bundle\BranchingBundle\Helper\MySql;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class LoadManager
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function run(Connection $connection, $filename, $dryRun)
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new \InvalidArgumentException(
                sprintf('File "%s" not found or not readable', $filename)
            );
        }

        $cat = new Process(['zcat', $filename]);
        $cat = $cat->getCommandLine();

        $mysql = MySql::buildConnectionArgs($connection);
        $mysql = new Process($mysql);
        $mysql = $mysql->getCommandLine();

        $cmd = [
            $cat,
            '|',
            $mysql,
        ];
        $cmd = implode(' ', $cmd);

        $this->logger->info($cmd);

        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout(3600);

        if (!$dryRun) {
            $process->mustRun();
        }

        $this->logger->info(sprintf('File "%s" loaded to "%s"', $filename, $connection->getDatabase()));
    }
}
