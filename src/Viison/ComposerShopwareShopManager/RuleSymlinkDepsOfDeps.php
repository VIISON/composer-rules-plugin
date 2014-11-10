<?php

namespace Viison\ComposerShopwareShopManager;

use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

use Composer\Installer\InstallationManager;
use Composer\Repository\RepositoryManager;

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

    /**
     * @var RepositoryManager
     */
    protected $repositoryManager;

    use DebugLog;

    public function __construct(array $params,
        InstallationManager $installationManager,
        RepositoryManager $repositoryManager)
    {
        $this->params = $params;
        $this->installationManager = $installationManager;
        $this->repositoryManager = $repositoryManager;
    }

    public function postInstall(PackageInterface $rootPackage,
        InstalledRepositoryInterface $repo,
        PackageInterface $package)
    {
        //$this->logMethod(__METHOD__, array($rootPackage, $repo, $package,
        //    $this->params));

        // FIXME: Composer seems to have some normalization rules ... Check.
        $matchOuterDeps = array_map('strtolower', $this->params[static::CONFIG_MATCH_OUTER_DEPS]);
        $matchInnerDeps = array_map('strtolower', $this->params[static::CONFIG_MATCH_INNER_DEPS]);

        if (!in_array($package->getName(), $matchOuterDeps, true)) {
            $this->logMethodStep(__METHOD__, array('Not matched: '
                . $package->getName() . ' with matches: ',
                $matchOuterDeps));
            return;
        }

        // FIXME: Check innerDeps are actually dependencies of $package.
        foreach ($matchInnerDeps as $matchInnerDep) {
            $innerDeps = $this->repositoryManager->findPackages($matchInnerDep, null);
            if (empty($innerDeps))
                throw new \Exception('Inner dependency ' . $matchInnerDep
                . ' of ' . $package->getName() . ' not found');

            foreach ($innerDeps as $innerDep)
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

        $this->logMethodStep(__METHOD__, array($inner, $outer, $symlinkDest,
            $symlinkDestPattern));

        if (!symlink($symlinkDest, $innerDir))
            throw new \Exception('Could not create symlink from '
                . $innerDir . ' to ' . $symlinkDest . ' for package '
                . $outer->getName() . '\'s inner dependency '
                . $inner->getName() . ' with rule config = '
                . json_encode($this->params));
    }
}
