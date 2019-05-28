<?php

namespace Octava\Bundle\BranchingBundle\Helper;

use Symfony\Component\Process\Process;

class Git
{
    public static function getCurrentBranch($dir)
    {
        $cmd = ['git', 'rev-parse', '--abbrev-ref', 'HEAD'];
        $process = new Process($cmd);
        $process->setWorkingDirectory($dir);
        $process->mustRun();
        $result = trim($process->getOutput());
        return $result;
    }

    public static function getRemoteBranches($dir)
    {
        $process = Process::fromShellCommandline('git fetch && git branch -r');
        $process->setWorkingDirectory($dir);
        $process->mustRun();
        $output = explode("\n", $process->getOutput());
        $remotes = array_filter($output);

        $process = Process::fromShellCommandline('git branch -l');
        $process->setWorkingDirectory($dir);
        $process->mustRun();
        $output = explode("\n", $process->getOutput());
        $locals = array_filter($output);

        $output = array_unique(array_merge($remotes, $locals));

        $branches = [];
        foreach ($output as $row) {
            if (strpos($row, ' -> ') === false) {
                $branches[] = trim(str_replace('origin/', '', $row));
            }
        }

        $branches = array_unique($branches);
        usort($branches, [__CLASS__, 'sortBranches']);

        return $branches;
    }

    public static function sortBranches($a, $b)
    {
        if ($a == 'master') {
            $result = -1;
        } elseif ($b == 'master') {
            $result = 1;
        } else {
            $result = ($a < $b) ? -1 : 1;
        }

        return $result;
    }
}
