<?php

namespace Octava\Bundle\BranchingBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Octava\Bundle\BranchingBundle\Service\DumpTable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DumpTablesCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    protected static $defaultName = 'octava:branching:dump-tables';

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    protected function configure()
    {
        $this
            ->addArgument(
                'entities',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'EntityName (example: AppAcmeBundle:Blog)'
            )
            ->addOption('dir', 'd', InputOption::VALUE_REQUIRED, 'Dump dirs (kernel.logs_dir/OctavaBranchingBundle)')
            ->setDescription('Make sql dump file for tables and translations by config');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface|Output $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = $this->getContainer()->get(DumpTable::class);
        $logger = $service->getLogger();
        if ($output->isDebug()) {
            $logger->pushHandler(new StreamHandler(STDOUT));
        } else {
            $logger->pushHandler(new NullHandler());
        }
        $logger->pushProcessor(new MemoryPeakUsageProcessor());

        $dir = $input->getOption('dir');
        if (!$dir) {
            $dir = $this->getContainer()->getParameter('kernel.logs_dir') . '/OctavaBranchingBundle';
        }
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        $allFilename = sprintf('%s/all.sql', $dir);
        file_put_contents($allFilename, '', FILE_APPEND);

        $entities = $input->getArgument('entities');
        if (empty($entities)) {
            $entities = $this->getContainer()
                ->get(DumpTable::class)
                ->getRepositories();
            if (!empty($entities)) {
                $logger->debug('Load entities from octava config', $entities);
            }
        }

        if (empty($entities)) {
            $logger->debug('Empty entities list, you could define list in config.yml');

            return;
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $tables = $this->findTables();

        foreach ($entities as $entityName) {
            $tableName = null;
            try {
                $tableName = $entityManager->getClassMetadata($entityName)->getTableName();
            } catch (ORMException $e) {
                continue;
            }
            if (!in_array($tableName, $tables)) {
                $logger->info(sprintf('Table "%s" does not exists in db', $tableName));
                break;
            }

            $dump = $service->generateSql($entityName);
            $filename = sprintf('%s/%s.sql', $dir, $tableName);
            file_put_contents($filename, '');
            file_put_contents($filename, implode("\n", $dump), FILE_APPEND);

            $gzFilename = $this->gzCompressFile($filename);
            if ($gzFilename) {
                unlink($filename);
                $logger->debug(sprintf('"%s" table dumped to file "%s"', $tableName, $gzFilename));
            } else {
                $logger->debug(sprintf('"%s" table dumped to file "%s"', $tableName, $filename));
            }

            file_put_contents($allFilename, implode("\n", $dump), FILE_APPEND);
        }

        $gzAllFilename = $this->gzCompressFile($allFilename);
        if ($gzAllFilename) {
            unlink($allFilename);
            $logger->debug(sprintf('All entities dump in file "%s"', $gzAllFilename));
        } else {
            $logger->debug(sprintf('All entities dump in file "%s"', $allFilename));
        }
    }

    protected function findTables()
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $schemaManager = $entityManager->getConnection()->getSchemaManager();
        $list = $schemaManager->listTables();
        $tables = [];
        foreach ($list as $item) {
            $tables[] = $item->getName();
        }

        return $tables;
    }

    protected function gzCompressFile($source, $level = 9)
    {
        $dest = $source . '.gz';
        $mode = 'wb' . $level;
        $error = false;
        if ($fp_out = gzopen($dest, $mode)) {
            if ($fp_in = fopen($source, 'rb')) {
                while (!feof($fp_in)) {
                    gzwrite($fp_out, fread($fp_in, 1024 * 512));
                }
                fclose($fp_in);
            } else {
                $error = true;
            }
            gzclose($fp_out);
        } else {
            $error = true;
        }
        if ($error) {
            return false;
        } else {
            return $dest;
        }
    }
}
