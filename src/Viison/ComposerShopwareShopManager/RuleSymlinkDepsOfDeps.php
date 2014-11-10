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
                $this->createSymlinks($package, $innerDep);
        }

        // Now we have to create symlinks for our outer, inner combination.
    }

    protected function createSymlinks(PackageInterface $outer, PackageInterface $inner)
    {
        $symlinkDestPatterns = $this->params[static::CONFIG_SYMLINK_DESTINATION];

        $innerDir = $this->installationManager->getInstallPath($inner);

        $matchVars = array('%outerdir%');
        $matchReplacements = array(
            $this->installationManager->getInstallPath($outer)
        );

        $symlinkDests = str_replace($matchVars, $matchReplacements,
            $symlinkDestPatterns);

        $this->logMethodStep(__METHOD__, array($inner, $outer, $symlinkDests,
            $symlinkDestPatterns));

        foreach ($symlinkDests as $symlinkDest)
            try {
                $this->createSymlink($symlinkDest, $innerDir);
            } catch (\Exception $cause) {
                throw new \Exception('Could not create symlink from '
                    . $innerDir . ' to ' . $symlinkDest . ' for package '
                    . $outer->getName() . '\'s inner dependency '
                    . $inner->getName() . ' with rule config = '
                    . json_encode($this->params),
                    0,
                    $cause);
            }
    }

    protected function createSymlink($dest, $src)
    {
        if (file_exists($dest)) {

            if (!is_link($dest))
                throw new \Exception('A file at ' . $dest
                    . ' already exists and is not a symbolic link.');

            $oldTarget = @readlink($dest);
            if ($oldTarget === false)
                throw new \Exception('The target of the symbolic link at
                ' . $dest . ' could not be read.');

            if ($oldTarget === $src)
                // Everything is fine, the link is already set up correctly:
                return;

            throw new \Exception('A symbolic link at ' . $dest
                . ' already exists. It points to ' . $oldTarget
                . ' but it should point to ' . $src . '.');
        }

        $wasCreated = false;
        $cause = null;
        try {
            $wasCreated = symlink($dest, $src);
        } catch (\Exception $cause) {
        }

        if ($wasCreated === false || isset($cause))
            throw new \Exception('Could not create symlink from '
                . $src. ' to ' . $dest,
                0,
                $cause);
    }
}
