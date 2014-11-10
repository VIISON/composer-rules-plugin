<?php

namespace Viison\ComposerShopwareShopManager;

use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

class RuleSymlinkDepsOfDeps extends EmptyRule {

    /**
     * @var array
     */
    protected $params;

    use DebugLog;

    public function __construct(array $params) {
        $this->params = $params;
    }

    public function postInstall(PackageInterface $rootPackage,
        InstalledRepositoryInterface $repo,
        PackageInterface $package) {
        $this->logMethod(__METHOD__, $rootPackage, $repo, $package);
    }
}
