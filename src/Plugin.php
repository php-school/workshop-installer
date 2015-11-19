<?php

namespace PhpSchool\PhpWorkshopInstaller;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * Class Plugin
 * @package PhpSchool\PhpWorkshopInstaller
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{

    /**
     * @var string
     */
    const PACKAGE_TYPE = 'php-school-workshop';

    /**
     * @var bool
     */
    private $ansiconInstalled = false;

    /**
     * Apply plugin modifications to composer
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            PackageEvents::POST_PACKAGE_INSTALL => [
                ['install', 0]  
            ],
            PackageEvents::POST_PACKAGE_UPDATE => [
                ['install', 0]
            ]
        );
    }

    /**
     * @param PackageEvent $event
     */
    public function install(PackageEvent $event)
    {
        if ($event->getOperation()->getJobType() !== 'install') {
            return;
        }

        if (static::PACKAGE_TYPE !== $event->getOperation()->getPackage()->getType()) {
            return;
        }
        
        $this->installAnsicon($event);
    }

    /**
     * @param PackageEvent $event
     */
    private function installAnsicon(PackageEvent $event)
    {
        if ($this->ansiconInstalled) {
            return;
        }

        $event->getIO()->write('1');
        //if not windows
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            return;
        }
        $event->getIO()->write('2');


        $this->ansiconInstalled = $this->checkAnsiconInstalled();

        if ($this->ansiconInstalled) {
            return;
        }
        $event->getIO()->write('3');
        
        $composer   = $event->getComposer();
        $vendorDir  = $composer->getConfig()->get('vendor-dir');
        
        $currentDir = str_replace('\\', '/', __DIR__);
        var_dump($currentDir);
        
        $path       = sprintf('%s/../ansicon/%d', $currentDir, $this->getArchitecture());
        var_dump($path);
        $ansiConDir = realpath($path);
        var_dump(file_exists($path));
        var_dump($ansiConDir);
        var_dump(file_exists($ansiConDir));
        
        foreach(new \DirectoryIterator($ansiConDir) as $file) {
            copy($file, $vendorDir);
        }

        $event->getIO()->write('<info>Installing Ansicon so console colours are supported.</info>');
        shell_exec(sprintf('setx path %s;%s', $vendorDir, getenv('PATH')));
        shell_exec('ansicon -i');

        $this->ansiconInstalled = true;
    }

    /**
     * @return bool
     */
    private function checkAnsiconInstalled()
    {
        $result = trim(shell_exec("where /F " . escapeshellarg('ansicon')), "\n\r");
        // "Where" can return several lines.
        return explode("\n", $result)[0];
    }

    /**
     * @return int
     */
    private function getArchitecture()
    {
        return 8 * PHP_INT_SIZE;
    }
}
