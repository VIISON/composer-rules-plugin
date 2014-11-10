<?php

namespace Viison\ComposerShopwareShopManager;

use Composer\Package\PackageInterface;
use Composer\Installer\InstallerInterface;
use Composer\Repository\InstalledRepositoryInterface;

class EmptyRule implements Rule {

    public function postInstall(PackageInterface $rootPackage,
        InstalledRepositoryInterface $repo,
        PackageInterface $package,
        InstallerInterface $mainInstaller)
    {
        return new RuleValueResult(null); // FIXME
    }

    public function canGetInstallPath(PackageInterface $rootPackage,
        PackageInterface $package, InstallerInterface $mainInstaller)
    {
        return new RuleValueResult(false);
    }

    public function getInstallPath(PackageInterface $rootPackage,
        PackageInterface $package, InstallerInterface $mainInstaller)
    {
        throw new \Exception('Not implemented');
    }

}
