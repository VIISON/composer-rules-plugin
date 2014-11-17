<?php

namespace Viison\ComposerRulesPlugin;

use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Installer\InstallerInterface;
use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Util\Filesystem;
use Composer\DependencyResolver\Operation\InstallOperation;

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

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(array $params,
        Composer $composer, IOInterface $io, Filesystem $filesystem)
    {
        $this->params = $params;
        $this->composer = $composer;
        $this->io = $io;
        $this->filesystem = $filesystem;
        $this->logger = new Logger($io); // FIXME: Should use singleton.
    }

    protected function normalizePkgName($name)
    {
        return strtolower($name);
    }

    public function postInstall(RuleResult $prevResult,
        PackageInterface $rootPackage,
        InstalledRepositoryInterface $repo,
        PackageInterface $package,
        InstallerInterface $mainInstaller)
    {
        $this->logger->logMethod(__METHOD__, array($package->getPrettyName(),
            $this->params));

        // FIXME: Composer seems to have some normalization rules ... Check.
        $matchOuterDeps = array_map(array($this, 'normalizePkgName'),
            $this->params[static::CONFIG_MATCH_OUTER_DEPS]);
        $matchInnerDeps = array_map(array($this, 'normalizePkgName'),
            $this->params[static::CONFIG_MATCH_INNER_DEPS]);

        if (!in_array($this->normalizePkgName($package->getName()),
                      $matchOuterDeps, true)) {
            $this->logger->logMethodStep(__METHOD__, array('Not matched: '
                . $package->getName() . ' with matches: ',
                $matchOuterDeps));
            return;
        }

        $this->logger->logMethodStep(__METHOD__, array('Matched: '
            . $package->getName() . ' with matches: ',
            $matchOuterDeps));

        /* We do not only install symlinks for $package, but for every
         * package we know about, unless the package is not installed yet.
         */

        // FIXME: Check innerDeps are actually dependencies of $package.
        foreach ($matchInnerDeps as $matchInnerDep) {
            $innerDeps = $this->composer->getRepositoryManager()
                ->findPackages($matchInnerDep, null);
            if (empty($innerDeps))
                throw new \Exception('Inner dependency ' . $matchInnerDep
                . ' of ' . $package->getName() . ' not found');

            foreach ($innerDeps as $innerDep)
                $this->createSymlinks($package, $innerDep, $repo);
        }

        // Now we have to create symlinks for our outer, inner combination.
    }

    protected function createSymlinks(PackageInterface $outer,
        PackageInterface $inner,
        InstalledRepositoryInterface $repo)
    {
        $symlinkDestPatterns = $this->params[static::CONFIG_SYMLINK_DESTINATION];

        $innerDir = $this->composer->getInstallationManager()->getInstallPath($inner);

        $matchVars = array('%outerdir%');
        $matchReplacements = array(
            $this->composer->getInstallationManager()->getInstallPath($outer)
        );

        $symlinkDests = str_replace($matchVars, $matchReplacements,
            $symlinkDestPatterns);

        $this->logger->logMethodStep(__METHOD__, array($inner, $outer, $symlinkDests,
            $symlinkDestPatterns));

        foreach ($symlinkDests as $symlinkDest)
            try {
                $this->createSymlink($symlinkDest, $innerDir,
                    $outer, $inner, $repo);
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

    protected function createSymlink($link, $target,
        PackageInterface $linkPackage, PackageInterface $targetPackage,
        InstalledRepositoryInterface $repo)
    {
        if (!is_dir($target))
            $this->composer->getInstallationManager()
                ->install($repo, new InstallOperation($targetPackage,
                    __METHOD__ . ' because it servers a target for a link.'));

        if (!is_dir($target))
            throw new \Exception('Installation of target did not happen at '
                . $target . '.');

        $targetRealpath = realpath($target);
        if ($targetRealpath === false)
            throw new \Exception('No realpath for ' . $target);

        if (file_exists($link)) {

            if (!is_link($link))
                throw new \Exception('A file at ' . $link
                    . ' already exists and is not a symbolic link. '
                    . '(stat = '
                    . json_encode(stat($link), JSON_PRETTY_PRINT) . ')');

            $oldTarget = @readlink($link);
            if ($oldTarget === false)
                throw new \Exception('The target of the symbolic link at
                ' . $link . ' could not be read.');

            // FIXME: Also check without realpath.
            if (realpath($oldTarget) === $targetRealpath)
                // Everything is fine, the link is already set up correctly:
                return;

            throw new \Exception('A symbolic link at ' . $link
                . ' already exists. It points to ' . $oldTarget
                . ' but it should point to ' . $target . '.');
        }

        $linkDir = dirname($link);
        if (!is_dir($linkDir)) {
            $this->logger->logMethodStep(__METHOD__, array($package->getPrettyName(),
                'Installing ' . $linkPackage->getPrettyName()
                . ' because it is need as a link target.'));
            $this->composer->getInstallationManager()
                ->install($repo, new InstallOperation($linkPackage,
                    __METHOD__ . ' because it needs a link.'));
        }

        if (!is_dir($linkDir))
            throw new \Exception('Installation of link package did not '
                . 'happen at ' . $linkDir. '.');

        $relativeTarget = $this->getRelativeTarget($targetRealpath, $link);

        $currentDir = getcwd();

        if (!chdir($linkDir))
            throw new \Exception('Could not change to directory '
                . $linkDir . ' to create a symbolic link in it.');

        $wasCreated = false;
        $cause = null;
        try {
            $wasCreated = symlink($relativeTarget, basename($link));
        } catch (\Exception $cause) {
        }

        if (!chdir($currentDir))
            throw new \Exception('Could not change back to directory '
                . $currentDir);

        if ($wasCreated === false || isset($cause))
            throw new \Exception('Could not create symlink to '
                . $target. ' at ' . $link
                . '. Attempted to `cd ' . $linkDir
                . ' && ln -s ' . $relativeTarget . ' '
                . $link . '`',
                0,
                $cause);
    }

    /**
     * @todo Probably broken on Windows: No support for drive letters
     */
    protected function getRelativeTarget($absoluteTarget, $link)
    {
        // Prevent empty parts:
        $link = preg_replace(
            ',' . DIRECTORY_SEPARATOR . '+$,u', '', $link);
        $link = preg_replace(
            ',' . DIRECTORY_SEPARATOR . '+,u',
            DIRECTORY_SEPARATOR,
            $link);

        $realLinkDir = realpath(dirname($link));
        if (empty($realLinkDir))
            throw new \Exception('Could not find the realpath for ' . $link);

        $absoluteLinkDir = $realLinkDir;

        // Prevent empty parts:
        $absoluteLinkDir = preg_replace(
            ',' . DIRECTORY_SEPARATOR . '+$,u', '', $absoluteLinkDir);
        $absoluteTarget = preg_replace(
            ',' . DIRECTORY_SEPARATOR . '+$,u', '', $absoluteTarget);

        $absoluteLinkDir = preg_replace(
            ',' . DIRECTORY_SEPARATOR . '+,u',
            DIRECTORY_SEPARATOR,
            $absoluteLinkDir);
        $absoluteTarget = preg_replace(
            ',' . DIRECTORY_SEPARATOR . '+$,u',
            DIRECTORY_SEPARATOR,
            $absoluteTarget);

        $targetParts = explode(DIRECTORY_SEPARATOR, $absoluteTarget);
        $linkDirParts = explode(DIRECTORY_SEPARATOR, $absoluteLinkDir);

        $targetPartsCount = count($targetParts);
        $linkDirPartsCount = count($linkDirParts);

        $minLength = min($targetPartsCount, $linkDirPartsCount);

        $matchingParts = 0;
        for ($i = 0; $i < $minLength; $i++) {
            if ($targetParts[$i] === $linkDirParts[$i]) {
                $matchingParts = $i + 1;
                continue;
            }
            break;
        }

        // Example:
        // target:     /a/b/c/d
        // link:       /a/b/e/f
        // link dir:   /a/b/e
        // rel target: ../c/d

        $unmatchingParts = $linkDirPartsCount - $matchingParts;

        $up = str_repeat('..' . DIRECTORY_SEPARATOR, $unmatchingParts);

        $down = '';
        for ($i = $matchingParts; $i < $targetPartsCount; $i++)
            $down .= $targetParts[$i] . DIRECTORY_SEPARATOR;

        $linkFile = basename($link);
        return $up . $down . $linkFile;
    }

}
