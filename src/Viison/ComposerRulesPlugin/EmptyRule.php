<?php

namespace Viison\ComposerRulesPlugin;

use Composer\Package\PackageInterface;
use Composer\Installer\InstallerInterface;
use Composer\Repository\InstalledRepositoryInterface;

class EmptyRule implements Rule
{
    public function postInstall(RuleResult $prevResult,
        PackageInterface $rootPackage,
        InstalledRepositoryInterface $repo,
        PackageInterface $package,
        InstallerInterface $mainInstaller)
    {
        return new RuleValueResult(null); // FIXME
    }

    public function canGetInstallPath(
        PackageInterface $rootPackage,
        PackageInterface $package, InstallerInterface $mainInstaller)
    {
        return false;
    }

    public function getInstallPath(RuleResult $prevResult,
        PackageInterface $rootPackage,
        PackageInterface $package, InstallerInterface $mainInstaller)
    {
        throw new \Exception('Not implemented');
    }
}
