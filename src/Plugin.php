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

        //if not windows
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            return;
        }

        $this->ansiconInstalled = $this->checkAnsiconInstalled();

        if ($this->ansiconInstalled) {
            return;
        }
        
        $composer   = $event->getComposer();
        $binDir     = str_replace('\\', '/', $composer->getConfig()->get('bin-dir'));
        $currentDir = str_replace('\\', '/', __DIR__);
        
        $path       = sprintf('%s/../ansicon/64', $currentDir);
        $ansiConDir = realpath($path);
        
        foreach(new \DirectoryIterator($ansiConDir) as $file) {
            if ($file->isDot()) {
                continue;
            }
            
            copy($file->getRealPath(), sprintf('%s/%s', $binDir, $file->getFilename()));
        }

        $event->getIO()->write('<info>Installing Ansicon so console colours are supported.</info>');
        
        $originalContent    = file_get_contents(__DIR__ . '/../set-path.ps1');
        $scriptContent      = str_replace('__COMPOSER_BIN__', $binDir, $originalContent);
        file_put_contents(__DIR__ . '/../set-path.ps1', $scriptContent);
        
        exec(sprintf('powershell -File %s', realpath(__DIR__ . '/../set-path.ps1')), $output, $return);
        
        var_dump($return);
        
        file_put_contents(__DIR__ . '/../set-path.ps1', $originalContent);

        $ansicon = str_replace('/', '\\', sprintf('%s/ansicon -i', $binDir));
        shell_exec($ansicon);
        
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
