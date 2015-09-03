<?php
namespace Octava\Bundle\BranchingBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Monolog\Handler\NullHandler;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Robo\LoggingBundle\Handler\StreamHandler;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpTablesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('octava:branching:dump-tables')
            ->addArgument(
                'entities',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'EntityName (example: RoboStructureBundle:Structure)'
            )
            ->addOption('dir', 'd', InputOption::VALUE_REQUIRED, 'Dump dirs (kernel.logs_dir/OctavaBranchingBundle)')
            ->setDescription('Make sql dump file for tables and translations by config');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = $this->getContainer()->get('octava_branching.service.dump_table');
        $logger = $service->getLogger();
        $verbosity = $output->getVerbosity();
        if ($verbosity > 1) {
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
            $logger->debug(sprintf('"%s" table dumped to file "%s"', $tableName, $filename));

            file_put_contents($allFilename, implode("\n", $dump), FILE_APPEND);
        }
        $logger->debug(sprintf('All entities dump in file "%s"', $allFilename));
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
}
