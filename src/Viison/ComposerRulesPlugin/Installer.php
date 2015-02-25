<?php
/**
 * VIISON/composer-rules-plugin
 *
 * Copyright (c) 2014-2015 VIISON GmbH
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * @license MIT <http://opensource.org/licenses/MIT>
 */

namespace Viison\ComposerRulesPlugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;

/**
 * Executes rules defined by a RuleEngine.
 */
class Installer extends LibraryInstaller
{
    use ComposerUtil;

    const CONFIG_VIISON_INSTALLER_KEY = 'composer-rules-plugin';
    const CONFIG_ROOT_DIR = 'root-dir';
    const CONFIG_AS_ROOT = 'as-root';
    const CONFIG_FALLBACK = 'fallback';

    const CONFIG_RULES = 'rules';

    /**
     * @var RuleEngine
     */
    private $ruleEngine;

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(
        IOInterface $io,
        Composer $composer,
        Filesystem $filesystem = null,
        Logger $logger)
    {
        $this->logger = $logger;
        $this->logger->logMethod(__METHOD__, array());
        parent::__construct($io, $composer, null, $filesystem);
    }

    protected function getRootPackage()
    {
        return $this->composer->getPackage();
    }

    protected function getNewRootDir()
    {
        return $this->getConfigValue(static::CONFIG_ROOT_DIR);
    }

    protected function getFallbackInstallerClassName()
    {
        return $this->getConfigValue(static::CONFIG_FALLBACK);
    }

    protected function getAsRoot()
    {
        return $this->getConfigValue(static::CONFIG_AS_ROOT);
    }

    /**
     * @return array
     */
    protected function getInstallerConfig()
    {
        $extra = $this->getRootPackage()->getExtra();
        if (!isset($extra)) {
            return array();
        }
        if (!is_array($extra)) {
            throw new \Exception(
                'The root package\'s "extra" configuration must be an array.');
        }
        if (!isset($extra[static::CONFIG_VIISON_INSTALLER_KEY])) {
            return array();
        }
        if (!is_array($extra)) {
            throw new \Exception(
                'The root package\'s "extra.'
                .static::CONFIG_VIISON_INSTALLER_KEY
                .'" configuration must be an array.');
        }

        return $extra[static::CONFIG_VIISON_INSTALLER_KEY];
    }

    protected function getInstallerRulesConfig()
    {
        $installerConfig = $this->getInstallerConfig();
        if (!isset($installerConfig[static::CONFIG_RULES])) {
            return array();
        }

        return $installerConfig[static::CONFIG_RULES];
    }

    protected function getConfigValue($key)
    {
        $installerConfig = $this->getInstallerConfig();
        if (!isset($installerConfig)) {
            throw new \Exception(
                'No configuration value found for '
                .'extra.'.static::CONFIG_VIISON_INSTALLER_KEY.'.'.$key
                .' under "extra" in the root package.');
        }

        return $installerConfig[$key];
    }

    public function supports($packageType)
    {
        return true;
    }

    protected function getRuleEngine()
    {
        if (isset($this->ruleEngine)) {
            return $this->ruleEngine;
        }
        $rules = $this->getInstallerRulesConfig();
        $ruleConfig = new RuleConfig($rules);
        $ruleFactory = new RuleFactory(
            $this->composer, $this->io, $this->filesystem, $this->logger);

        return $this->ruleEngine = new RuleEngine($ruleConfig, $ruleFactory,
            $this->logger);
    }

    public function install(InstalledRepositoryInterface $repo,
        PackageInterface $package)
    {
        $this->logger->logMethod(__METHOD__, array($repo, $package));
        parent::install($repo, $package);

        return $this->runPostInstallForPackage($repo, $package);
    }

    /**
     * @var array List of package names for which post install has been run.
     */
    protected $postInstallRunFor = array();

    /**
     * Executes post install rules for a given package.
     *
     * @see install() install() calls this
     * @see runRemainingPostInstalls() runRemainingPostInstalls() calls this
     */
    protected function runPostInstallForPackage(
        InstalledRepositoryInterface $repo,
        PackageInterface $package)
    {
        $retVal = $this->getRuleEngine()->postInstall($this->getRootPackage(),
            $repo, $package, $this);
        $this->postInstallRunFor[] = $this->normalizePackageName(
            $package->getName());

        return $retVal;
    }

    /**
     * Executes post install rules for packages where they have not yet been
     * executed.
     *
     * With Composer, this can happen when this plugin was activated late
     * during the installation process or when an update only affected some
     * packages.
     *
     * @see Plugin::onPostInstallCmd() calls this
     * @see Plugin::onPostUpdateCmd() calls this
     */
    public function runRemainingPostInstalls()
    {
        $allPackages = $this->getAllPackagesRecursively();

        $packageMap = array();
        $allPackageNames = array();
        foreach ($allPackages as $package) {
            $packageName = $this->normalizePackageName(
                $package->getName());
            $allPackageNames[] = $packageName;
            $packageMap[$packageName] = $package;
        }

        $remainingPostInstalls = array_diff($allPackageNames,
            $this->postInstallRunFor);

        $this->logger->logMethod(__METHOD__,
            array('remaining post installs: ', $remainingPostInstalls));

        $localRepo = $this->composer->getRepositoryManager()
            ->getLocalRepository();

        foreach ($remainingPostInstalls as $remainingPostInstall) {
            $package = $packageMap[$remainingPostInstall];
            $this->runPostInstallForPackage($localRepo, $package);
        }
    }

    /**
     * @FIXME Check whether this actually gets recursive dependencies.
     */
    protected function getAllPackagesRecursively()
    {
        $localRepo = $this->composer->getRepositoryManager()
            ->getLocalRepository();

        return $localRepo->getCanonicalPackages();
    }

    /**
     * @FIXME: Need to evaluate whether this needs to be considered.
     */
    public function update(InstalledRepositoryInterface $repo,
        PackageInterface $initial, PackageInterface $target)
    {
        $this->logger->logMethod(__METHOD__, array($repo, $initial, $target));

        return parent::update($repo, $initial, $target);
    }

    /**
     * @FIXME: Will need to handle this in the future (i.e. remove old
     *     symlinks).
     */
    public function uninstall(InstalledRepositoryInterface $repo,
        PackageInterface $package)
    {
        $this->logger->logMethod(__METHOD__, array($repo, $package));

        return parent::uninstall($repo, $package);
    }

    /**
     * Uses the RuleEngine to determine the directory in which a package
     * should be installed.
     */
    public function getInstallPath(PackageInterface $package)
    {
        $this->logger->logMethod(__METHOD__, array($package));

        $ruleEngine = $this->getRuleEngine();

        $installPath = $ruleEngine->getInstallPath(
            $this->getRootPackage(),
            $package,
            $this);

        if (!isset($installPath)) {
            $installPath = parent::getInstallPath($package);
            $this->logger->logMethodStep(__METHOD__,
                array('->   ordinary installPath', $installPath));
        } else {
            $this->logger->logMethodStep(__METHOD__,
                array('-> ruleEngine installPath', $installPath));
        }

        return $installPath;
    }
}
