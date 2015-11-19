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

        $this->ansiconInstalled = $this->isAnsiconInstalled();

        if ($this->ansiconInstalled) {
            return;
        }

        $composer   = $event->getComposer();
        $binDir     = $this->unixPath($composer->getConfig()->get('bin-dir'));
        $ansiConDir = realpath(sprintf('%s/../ansicon/64', $this->unixPath(__DIR__)));

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

        exec(
            sprintf(
                'powershell -File %s', 
                $this->windowsPath(realpath($this->unixPath(__DIR__) . '/../set-path.ps1'))
            ), 
            $output, 
            $return
        );

        //return code seems to be always 0 even when error's
        //however, there is no output on success
        if (!empty($output)) {
            throw new \RuntimeException('Setting environment failed. Please run in a shell with admin privileges');
        }
        file_put_contents($this->unixPath(__DIR__) . '/../set-path.ps1', $originalContent);
        shell_exec($this->windowsPath(sprintf('%s/ansicon -i', $binDir)));

        $this->ansiconInstalled = true;
    }

    /**
     * @return bool
     */
    private function isAnsiconInstalled()
    {
        exec("where /F /Q ansicon", $output, $return);
        return $return == 0;
    }

    /**
     * @param string $path
     * @return string
     */
    private function unixPath($path)
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * @param string $path
     * @return string
     */
    public function windowsPath($path)
    {
        return str_replace('/', '\\', $path);
    }

    /**
     * @return int
     */
    private function getArchitecture()
    {
        return 8 * PHP_INT_SIZE;
    }
}
