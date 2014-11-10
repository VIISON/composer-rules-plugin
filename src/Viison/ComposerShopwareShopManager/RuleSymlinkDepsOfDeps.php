<?php

namespace Viison\ComposerShopwareShopManager;

use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Installer\InstallerInterface;

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

    use DebugLog;

    public function __construct(array $params,
        Composer $composer, IOInterface $io, Filesystem $filesystem)
    {
        $this->params = $params;
        $this->installationManager = $installationManager;
        $this->repositoryManager = $repositoryManager;
    }

    public function postInstall(RuleResult $prevResult,
        PackageInterface $rootPackage,
        InstalledRepositoryInterface $repo,
        PackageInterface $package,
        InstallerInterface $mainInstaller)
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
            $innerDeps = $this->composer->getRepositoryManager()
                ->findPackages($matchInnerDep, null);
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

        $innerDir = $this->composer->getInstallationManager()->getInstallPath($inner);

        $matchVars = array('%outerdir%');
        $matchReplacements = array(
            $this->composer->getInstallationManager()->getInstallPath($outer)
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

    protected function createSymlink($link, $target)
    {
        if (file_exists($link)) {

            if (!is_link($link))
                throw new \Exception('A file at ' . $link
                    . ' already exists and is not a symbolic link.');

            $oldTarget = @readlink($link);
            if ($oldTarget === false)
                throw new \Exception('The target of the symbolic link at
                ' . $link . ' could not be read.');

            if ($oldTarget === $target)
                // Everything is fine, the link is already set up correctly:
                return;

            throw new \Exception('A symbolic link at ' . $link
                . ' already exists. It points to ' . $oldTarget
                . ' but it should point to ' . $target . '.');
        }

        $wasCreated = false;
        $cause = null;
        try {
            $wasCreated = symlink($target, $link);
        } catch (\Exception $cause) {
        }

        if ($wasCreated === false || isset($cause))
            throw new \Exception('Could not create symlink to '
                . $target. ' at ' . $link,
                0,
                $cause);
    }
}
