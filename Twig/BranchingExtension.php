<?php

namespace Octava\Bundle\BranchingBundle\Twig;

use Octava\Bundle\BranchingBundle\Helper\Git;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BranchingExtension extends AbstractExtension
{
    const CURRENT_BRANCH = 'octava_current_branch';
    /**
     * @var string
     */
    protected $projectDir;

    /**
     * @var string
     */
    protected $environment;

    public function __construct($projectDir, $environment)
    {
        $this->projectDir = $projectDir;
        $this->environment = $environment;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'octava_branching_extension';
    }

    public function getFunctions()
    {
        return [
            self::CURRENT_BRANCH => new TwigFunction(self::CURRENT_BRANCH, [$this, 'getCurrentBranch'])
        ];
    }

    public function getCurrentBranch($prefix = '', $postfix = '')
    {
        $result = null;
        if (in_array($this->environment, ['dev', 'test'])) {
            $result = sprintf(
                '%s%s (%s)%s',
                $prefix,
                Git::getCurrentBranch($this->projectDir),
                $this->environment,
                $postfix
            );
        }
        return $result;
    }
}
