<?php

namespace PhpSchool\PhpWorkshopInstaller;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use DomainException;

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
            ],
            PackageEvents::POST_PACKAGE_UNINSTALL => [
                ['unInstall', 0]
            ],
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

        $binary         = basename(array_values($binaries)[0]);
        $binaryLocation = sprintf('%s/%s', $binLocation, $binary);
        $target         = $this->getInstallLocation($binary);


        //if target exists and is symlink then we just remove it
        if (is_link($target)) {
            unlink($target);
        }

        if (is_writable(dirname($target))) {
            return symlink($binaryLocation, $target);
        }

        $event->getIO()->write(
            sprintf(
                '<error>The directory: %s is not writeable. The workshop %s cannot be installed.</error>',
                dirname($target),
                $binary
            )
        );
        $event->getIO()->write("");
        $event->getIO()->write(sprintf('You have two options now:'));
        $event->getIO()->write(sprintf(' 1. Add the composer global bin dir: <info>%s</info> to your PATH variable', $binLocation));
        $event->getIO()->write(sprintf(' 2. Run <info>%s</info> directly with <info>%s</info>', $binary, $binaryLocation));
        $event->getIO()->write("");
    }

    /**
     * @param PackageEvent $event
     */
    public function unInstall(PackageEvent $event)
    {
        if (static::PACKAGE_TYPE !== $event->getOperation()->getPackage()->getType()) {
            return;
        }

        $this->unInstallBinary($event);
    }

    /**
     * @param PackageEvent $event
     * @return bool|void
     */
    public function unInstallBinary(PackageEvent $event)
    {
        $binaries = $event
            ->getOperation()
            ->getPackage()
            ->getBinaries();

        if (count($binaries) === 0) {
            return;
        }

        $binary = basename(array_values($binaries)[0]);
        $target = $this->getInstallLocation($binary);

        //remove link if it exists
        if (is_link($target)) {
            unlink($target);
        }
    }


    /**
     * @param string $binary
     * @return string
     */
    private function getInstallLocation($binary)
    {
        $os = PHP_OS;
        if (strpos($os, 'Darwin') === 0) {
            //mac
            $target = sprintf('/usr/local/bin/%s', $binary);
        } elseif (strpos($os, 'WIN') === 0) {
            //windows
            throw new DomainException(sprintf('Windows is not supported'));
        } else {
            //linux
            $target = sprintf('/usr/local/bin/%s', $binary);
        }

        return $target;
    }
}
