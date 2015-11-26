<?php
namespace Octava\Bundle\BranchingBundle\Command;

use Doctrine\DBAL\DriverManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DropOldDbCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('octava:branching:drop-old')
            ->setDescription('Drop databases for non existent branches')
            ->addOption('branch', 'b', InputOption::VALUE_REQUIRED, 'Branch name')
            ->addOption('test', 't', InputOption::VALUE_NONE, 'Test mode')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Delete without confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Run command drop old</info>');
        $output->writeln(sprintf('<info>Force mode: %d</info>', $input->getOption('force')));
        $output->writeln(sprintf('<info>Test mode: %d</info>', $input->getOption('test')));

        $branch = $this->convertBranchToDatabaseName($input->getOption('branch'));
        $branches = [];
        if (!$branch) {
            $branches = $this->getPreparedBranchNames();
            $output->writeln(sprintf('<info>Exists branches: %s</info>', implode(', ', $branches)));
        } else {
            if ('master' === strtolower($branch)) {
                throw new \InvalidArgumentException('Master is cannot be deleted');
            }
        }

        $databases = $this->getProjectDatabases();
        $output->writeln(sprintf('<info>Databases: %s</info>', implode(', ', $databases)));

        foreach ($databases as $database) {
            preg_match('|_branch_(.+)$|ius', $database, $matches);
            if (isset($matches[1])) {
                if ($branch && $matches[1] === $branch) {
                    $this->dropDatabaseByName($database, $input, $output);
                } elseif (!$branch && !in_array($matches[1], $branches, true)) {
                    $this->dropDatabaseByName($database, $input, $output);
                }
            }
        }
    }

    /**
     * Получить список ветвей в формате сегмента названия БД
     * @return array
     */
    protected function getPreparedBranchNames()
    {
        exec('git remote update');
        exec('git fetch -p');
        exec('git branch -a', $branches);

        $result = [];
        foreach ($branches as $branch) {
            $pos = strrpos($branch, '/');
            if (false !== $pos) {
                $branch = substr($branch, $pos + 1);
                if (empty($branch)) {
                    continue;
                }
            }
            $branch = str_replace('*', '', $branch);
            $branch = trim($branch);
            if ($branch !== 'master') {
                $result[] = $this->convertBranchToDatabaseName($branch);
            }
        }
        $result = array_unique($result);
        $result = array_values($result);

        return $result;
    }

    /**
     * Получить список баз данных ветвей проекта
     * @return array
     * @throws \RuntimeException
     */
    protected function getProjectDatabases()
    {
        $connection = $this->getContainer()->get('doctrine')->getConnection('default');
        $params = $connection->getParams();
        unset($params['dbname']);

        $tmpConnection = DriverManager::getConnection($params);
        $allDatabases = $tmpConnection->getSchemaManager()->listDatabases();

        $container = $this->getContainer();
        $originalDatabaseName = $container->getParameter('database_name');
        if ($container->hasParameter('database_name_original')) {
            $originalDatabaseName = $container->getParameter('database_name_original');
        }
        foreach ($allDatabases as $key => $value) {
            if (0 !== strpos($value, $originalDatabaseName)) {
                unset($allDatabases[$key]);
            }
        }

        return $allDatabases;
    }

    /**
     * Drop database by name
     * @param string $name
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param OutputInterface $output
     */
    protected function dropDatabaseByName($name, InputInterface $input, OutputInterface $output)
    {
        $confirmation = $input->getOption('force');
        if (!$input->getOption('force')) {
            /** @var \Symfony\Component\Console\Helper\DialogHelper $dialog */
            $dialog = $this->getHelperSet()->get('dialog');
            $confirmation = $dialog->askConfirmation(
                $output,
                sprintf('Found database without related branch <info>%s</info>. Drop it? [Y/n]:', $name),
                false
            );
        }

        if ($confirmation) {
            $connection = $this->getContainer()->get('doctrine')->getConnection('default');
            $name = $connection->getDatabasePlatform()->quoteSingleIdentifier($name);
            try {
                if (!$input->getOption('test')) {
                    $connection->getSchemaManager()->dropDatabase($name);
                }
                $output->writeln(sprintf('<info>Dropped database named <comment>%s</comment></info>', $name));
            } catch (\Exception $e) {
                $output->writeln(
                    sprintf('<error>Could not drop database for connection named <comment>%s</comment></error>', $name)
                );
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            }
        }
    }

    /**
     * @param string $branchName
     * @return string
     */
    protected function convertBranchToDatabaseName($branchName)
    {
        $result = str_replace('-', '_', strtolower($branchName));
        $pos = strrpos($branchName, '/');
        if (false !== $pos) {
            $result = substr($result, $pos + 1);
        }

        return $result;
    }
}
