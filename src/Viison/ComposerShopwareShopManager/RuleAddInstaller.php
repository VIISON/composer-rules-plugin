<?php

namespace Viison\ComposerShopwareShopManager;

use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Installer\InstallerInterface;
use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Util\Filesystem;

class RuleAddInstaller extends EmptyRule {

    use DebugLog;

    const CONFIG_INSTALLER_CLASS = 'class';

    /**
     * @var InstallerInterface
     */
    private $subInstaller;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(array $params,
        Composer $composer, IOInterface $io, Filesystem $filesystem)
    {
        $this->params = $params;
        $this->composer = $composer;
        $this->io = $io;
        $this->filesystem = $filesystem;
    }

    protected function getSubInstaller()
    {
        if (isset($this->subInstaller))
            return $this->subInstaller;

        $className = $this->params[static::CONFIG_INSTALLER_CLASS];
        return $this->subInstaller = new $className(
            $this->io, $this->composer, null, $this->filesystem);
    }

    public function canGetInstallPath(PackageInterface $rootPackage,
        PackageInterface $package, InstallerInterface $mainInstaller)
    {
        $supports = $this->getSubInstaller()->supports($package->getType());
        $this->logMethodStep(__METHOD__, array($package, $supports));
        return $supports;
    }

    public function getInstallPath(RuleResult $prevResult,
        PackageInterface $rootPackage,
        PackageInterface $package, InstallerInterface $mainInstaller)
    {
        $installPath = $this->getSubInstaller()->getInstallPath($package);
        $this->logMethodStep(__METHOD__, array($package, $installPath));
        return new RuleValueResult($installPath);
    }

}
