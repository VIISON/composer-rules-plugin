<?php

namespace Viison\ComposerShopwareShopManager;

use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

use Composer\Installer\InstallationManager;

class RuleSymlinkDepsOfDeps extends EmptyRule {

    const CONFIG_MATCH_OUTER_DEPS = 'match-outer-deps';
    const CONFIG_MATCH_INNER_DEPS = 'match-inner-deps';
    const CONFIG_SYMLINK_DESTINATION = 'symlink-dest';

    /**
     * @var array
     */
    protected $params;

    /**
     * @var InstallationManager
     */
    protected $installationManager;

    use DebugLog;

    public function __construct(array $params,
        InstallationManager $installationManager)
    {
        $this->params = $params;
        $this->installationManager = $installationManager;
    }

    public function postInstall(PackageInterface $rootPackage,
        InstalledRepositoryInterface $repo,
        PackageInterface $package)
    {
        $this->logMethod(__METHOD__, array($rootPackage, $repo, $package,
            $this->params));

        $matchOuterDeps = $this->params[static::CONFIG_MATCH_OUTER_DEPS];
        $matchInnerDeps = $this->params[static::CONFIG_MATCH_INNER_DEPS];

        if (!in_array($package->getName(), $matchOuterDeps, true)) {
            $this->logMethodStep(__METHOD__, array('Not matched: '
                . $package->getName() . ' with matches: ',
                $matchOuterDeps));
            return;
        }

        // FIXME: Check innerDeps are actually dependencies of $package.
        $innerDeps = array();
        foreach ($matchInnerDeps as $matchInnerDep) {
            $innerDep = $repo->findPackages($matchInnerDep);
            if (!isset($innerDep))
                throw new \Exception('Inner dependency ' . $matchInnerDep
                . ' of ' . $package->getName() . ' not found');

            $this->createSymlink($package, $innerDep);
        }

        // Now we have to create symlinks for our outer, inner combination.
    }

    protected function createSymlink(PackageInterface $outer, PackageInterface $inner)
    {
        $symlinkDestPattern = $this->params[static::CONFIG_SYMLINK_DESTINATION];

        $innerDir = $this->installationManager->getInstallPath($inner);

        $matchVars = array('%outerdir%');
        $matchReplacements = array(
            $this->installationManager->getInstallPath($outer)
        );

        $symlinkDest = str_replace($matchVars, $matchReplacements,
            $symlinkDestPattern);

        $this->logMethodStep(__METHOD__, array($inner, $outer, $symlinkDest));
        if (!symlink($symlinkDest, $innerDir))
            throw new \Exception('Could not create symlink from '
                . $innerDir . ' to ' . $symlinkDest . ' for package '
                . $outer->getName() . '\'s inner dependency '
                . $inner->getName() . ' with rule config = '
                . json_encode($this->params));
    }
}
