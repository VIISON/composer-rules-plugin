<?php

namespace Viison\ComposerShopwareShopManager;

use Composer\Package\PackageInterface;
use Composer\Installer\InstallerInterface;
use Composer\Repository\InstalledRepositoryInterface;

interface Rule {

    /**
     * @return void
     */
    public function postInstall(PackageInterface $rootPackage,
        InstalledRepositoryInterface $repo,
        PackageInterface $package,
        InstallerInterface $mainInstaller);

    /**
     * @return boolean
     */
    public function canGetInstallPath(PackageInterface $rootPackage,
        PackageInterface $package, InstallerInterface $mainInstaller);

    public function getInstallPath(PackageInterface $rootPackage,
        PackageInterface $package, InstallerInterface $mainInstaller);

}
