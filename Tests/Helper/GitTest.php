<?php

namespace Octava\Bundle\BranchingBundle\Tests;

use Octava\Bundle\BranchingBundle\Helper\Git;

class GitTest extends \PHPUnit_Framework_TestCase
{
    protected $dir;

    public function setUp()
    {
        $this->dir = dirname(dirname(__DIR__));
    }

    public function testGetCurrentBranch()
    {
        $actual = Git::getCurrentBranch($this->dir);
        $this->assertEquals('master', $actual);
    }

    public function testGetRemoteBranches()
    {
        $actual = Git::getRemoteBranches($this->dir);
        $this->assertTrue(in_array('master', $actual));
    }
}
