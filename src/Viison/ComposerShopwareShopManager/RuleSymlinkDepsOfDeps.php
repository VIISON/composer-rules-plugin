<?php

namespace Viison\ComposerShopwareShopManager;

use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

class RuleSymlinkDepsOfDeps extends EmptyRule {

    const CONFIG_MATCH_OUTER_DEPS = 'match-outer-deps';
    const CONFIG_MATCH_INNER_DEPS = 'match-inner-deps';
    const CONFIG_SYMLINK_DESTINATION = 'symlink-dest';

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
        PackageInterface $package)
    {
        $this->logMethod(__METHOD__, array($rootPackage, $repo, $package,
            $this->params));

        $matchOuterDeps = $this->params[static::CONFIG_MATCH_OUTER_DEPS];
        $matchInnerDeps = $this->params[static::CONFIG_MATCH_INNER_DEPS];
        $symlinkDest = $this->params[static::CONFIG_SYMLINK_DESTINATION];

        if (!in_array($package->getName(), $matchOuterDeps, true)) {
            $this->logMethodStep(__METHOD__, array('Not matched: '
                . $package->getName() . ' with matches: ',
                $matchOuterDeps));
            return;
        }
    }
}
