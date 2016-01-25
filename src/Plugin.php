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

        $this->installBinary($event);
    }

    /**
     * @param PackageEvent $event
     * @return bool|void
     */
    public function installBinary(PackageEvent $event)
    {
        $binLocation = $event
            ->getComposer()
            ->getConfig()
            ->get('bin-dir');

        $binaries = $event
            ->getOperation()
            ->getPackage()
            ->getBinaries();

        if (count($binaries) === 0) {
            return;
        }

        $binary = basename(array_values($binaries)[0]);
        $binaryLocation = sprintf('%s/%s', $binLocation, $binary);


        $os = PHP_OS;
        if (strpos($os, 'Darwin') === 0) {
            //mac
            return symlink($binaryLocation, sprintf('/usr/local/bin/%s', $binary));
        }

        if (strpos($os, 'WIN') === 0) {
            //windows
            return;
        }

        //linux
        return symlink($binaryLocation, sprintf('/usr/local/bin/%s', $binary));
    }
}
