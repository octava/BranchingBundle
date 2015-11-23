<?php
namespace Octava\Bundle\BranchingBundle\Composer;

use Composer\Script\CommandEvent;
use Symfony\Component\Yaml\Dumper;

class ScriptHandler
{
    /**
     * Composer variables are declared static so that an event could update
     * a composer.json and set new options, making them immediately available
     * to forthcoming listeners.
     */
    protected static $options = [
        'symfony-app-dir' => 'app',
        'file-name' => 'parameters_ext.yml'
    ];

    public static function buildParameters(CommandEvent $event)
    {
        $options = static::getOptions($event);
        $rootDir = getcwd();
        $appDir = $options['symfony-app-dir'];


        $date = new \DateTime('now');
        $date->setTimezone(new \DateTimeZone('UTC'));

        $parameters = [
            'parameters' => [
                'build_time' => $date->getTimestamp()
            ]
        ];
        $dumper = new Dumper();
        $yaml = $dumper->dump($parameters, 2);

        $filename = $rootDir . DIRECTORY_SEPARATOR
            . $appDir . DIRECTORY_SEPARATOR
            . 'config' . DIRECTORY_SEPARATOR
            . $options['file-name'];
        file_put_contents($filename, $yaml);
        $event->getIO()->write("Generated config parameters ($filename)");
    }

    protected static function getOptions(CommandEvent $event)
    {
        $options = array_merge(static::$options, $event->getComposer()->getPackage()->getExtra());

        $options['process-timeout'] = $event->getComposer()->getConfig()->get('process-timeout');

        return $options;
    }
}
