<?php

namespace Viison\ComposerShopwareShopManager;

use Composer\Package\PackageInterface;
use Composer\Installer\InstallerInterface;
use Composer\Repository\InstalledRepositoryInterface;

interface Rule {

    /**
     * @return void
     */
    public function postInstall(RuleResult $prevResult, PackageInterface
        $rootPackage,
        InstalledRepositoryInterface $repo,
        PackageInterface $package,
        InstallerInterface $mainInstaller);

    /**
     * @return boolean
     */
    public function canGetInstallPath(PackageInterface $rootPackage,
        PackageInterface $package, InstallerInterface $mainInstaller);

    public function getInstallPath(RuleResult $prevResult,
        PackageInterface $rootPackage,
        PackageInterface $package, InstallerInterface $mainInstaller);

}
