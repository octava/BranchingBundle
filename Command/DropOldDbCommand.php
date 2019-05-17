<?php

namespace Octava\Bundle\BranchingBundle\Command;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DropOldDbCommand
 * @package Octava\Bundle\BranchingBundle\Command
 */
class DropOldDbCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    protected static $defaultName = 'octava:branching:drop-old';
    /**
     * @var SymfonyStyle
     */
    protected $symfonyStyle;

    /**
     * @return SymfonyStyle
     */
    public function getSymfonyStyle()
    {
        return $this->symfonyStyle;
    }

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
            ->setDescription('Drop databases for non existent branches')
            ->addOption('branch', 'b', InputOption::VALUE_REQUIRED, 'Branch name')
            ->addOption('test', 't', InputOption::VALUE_NONE, 'Test mode')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Delete without confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);

        $this->getSymfonyStyle()->title('Run command drop old');
        $this->getSymfonyStyle()->text(
            [
                sprintf('Force mode: %d', $input->getOption('force')),
                sprintf('Test mode: %d', $input->getOption('test')),
            ]
        );

        $branch = $this->convertBranchToDatabaseName($input->getOption('branch'));
        $branches = [];
        if (!$branch) {
            $branches = $this->getPreparedBranchNames();
            $this->getSymfonyStyle()->writeln(sprintf('Exists branches: %s', implode(', ', $branches)));
        } else {
            if ('master' === strtolower($branch)) {
                throw new \InvalidArgumentException('Master is cannot be deleted');
            }
        }

        $databases = $this->getProjectDatabases();
        $this->getSymfonyStyle()->writeln(sprintf('Databases: %s', implode(', ', $databases)));

        foreach ($databases as $database) {
            preg_match('|_branch_(.+)$|ius', $database, $matches);
            if (isset($matches[1])) {
                if ($branch && $matches[1] === $branch) {
                    $this->dropDatabaseByName($database, $input);
                } elseif (!$branch && !in_array($matches[1], $branches, true)) {
                    $this->dropDatabaseByName($database, $input);
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
        if (!file_exists('.git')) {
            throw new \RuntimeException('Dir "' . getcwd() . '" is not git repository"');
        }
        exec('git remote update');
        exec('git fetch -p');
        exec('git ls-remote --heads', $branches);

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
     * @param InputInterface $input
     */
    protected function dropDatabaseByName($name, InputInterface $input)
    {
        $confirmation = $input->getOption('force');
        if (!$input->getOption('force')) {
            $confirmation = $this->getSymfonyStyle()->confirm(
                sprintf('Found database without related branch <info>%s</info>. Drop it? [Y/n]:', $name)
            );
        }

        if ($confirmation) {
            $connection = $this->getContainer()->get('doctrine')->getConnection('default');
            $name = $connection->getDatabasePlatform()->quoteSingleIdentifier($name);
            try {
                if (!$input->getOption('test')) {
                    $connection->getSchemaManager()->dropDatabase($name);
                }
                $this->getSymfonyStyle()->success(
                    sprintf('Dropped database named "%s"', $name)
                );
            } catch (\Exception $e) {
                $this->getSymfonyStyle()->error(
                    sprintf('Could not drop database for connection named "%s"', $name)
                );
                $this->getSymfonyStyle()->error($e->getMessage());
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
