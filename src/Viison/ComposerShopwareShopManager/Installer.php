<?php

namespace Viison\ComposerShopwareShopManager;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;

class Installer extends LibraryInstaller {

    use DebugLog;

    const CONFIG_VIISON_INSTALLER_KEY = 'viison-installer';
    const CONFIG_ROOT_DIR = 'root-dir';
    const CONFIG_AS_ROOT = 'as-root';
    const CONFIG_FALLBACK = 'fallback';

    const CONFIG_RULES = 'rules';

    /**
     * @var RuleEngine
     */
    private $ruleEngine;

    public function __construct(
        IOInterface $io,
        Composer $composer,
        Filesystem $filesystem = null)
    {
        parent::__construct($io, $composer, null, $filesystem);
        $this->checkConfig();
    }

    protected function checkConfig()
    {
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
        if (!isset($extra))
            return array();
        if (!is_array($extra))
            throw new \Exception(
                'The root package\'s "extra" configuration must be an array.');
        if (!isset($extra[static::CONFIG_VIISON_INSTALLER_KEY]))
            return array();
        if (!is_array($extra))
            throw new \Exception(
                'The root package\'s "extra.'
                . static::CONFIG_VIISON_INSTALLER_KEY
                . '" configuration must be an array.');
        return $extra[static::CONFIG_VIISON_INSTALLER_KEY];
    }

    protected function getInstallerRulesConfig()
    {
        $installerConfig = $this->getInstallerConfig();
        if (!isset($installerConfig[static::CONFIG_RULES]))
            return array();
        return $installerConfig[static::CONFIG_RULES];
    }

    protected function getConfigValue($key)
    {
        $installerConfig = $this->getInstallerConfig();
        if (!isset($installerConfig))
            throw new \Exception(
                'No configuration value found for '
                . 'extra.' . static::CONFIG_VIISON_INSTALLER_KEY . '.'  . $key
                . ' under "extra" in the root package.');
        return $installerConfig[$key];
    }

    public function supports($packageType)
    {
        return true;
    }

    protected function getRuleEngine()
    {
        if (isset($this->ruleEngine))
            return $this->ruleEngine;
        $rules = $this->getInstallerRulesConfig();
        $ruleConfig = new RuleConfig($rules);
        $ruleFactory = new RuleFactory(
            $this->composer, $this->io, $this->filesystem);
        return $this->ruleEngine = new RuleEngine($ruleConfig, $ruleFactory);
    }

    public function isInstalled(InstalledRepositoryInterface $repo,
        PackageInterface $package)
    {
        $this->logMethod(__METHOD__, array($repo, $package));
        return parent::isInstalled($repo, $package);
    }

    public function install(InstalledRepositoryInterface $repo,
        PackageInterface $package)
    {
        $this->logMethod(__METHOD__, array($repo, $package));
        parent::install($repo, $package);
        return $this->getRuleEngine()->postInstall($this->getRootPackage(),
            $repo, $package, $this);
    }

    public function getInstallPath(PackageInterface $package)
    {
        $this->logMethod(__METHOD__, array($package));

        $rulesEngine = $this->getRulesEngine();
        $canGetInstallPath = $rulesEngine->canGetInstallPath(
            $this->getRootPackage(),
            $package,
            $this);

        if (!$canGetInstallPath)
            return parent::getInstallPath($package);

        return $rulesEngine->getInstallPath(
            $this->getRootPackage(),
            $package,
            $this);
    }

    protected function getFallbackInstaller()
    {
        if (isset($this->fallbackInstaller))
            return $this->fallbackInstaller;
        $className = $this->getFallbackInstallerClassName();
        echo "    ", __METHOD__, ": className = $className\n";
        $this->fallbackInstaller = new $className(
            $this->io, $this->composer, null, $this->filesystem);
        return $this->fallbackInstaller;
    }

    /**
     * Replants every part of $dir below $oldBase on top of $newBase.
     *
     * $oldBase and $newBase must be absolute paths without '/' at the end.
     *
     * @todo May be broken on Windows.
     * @todo Probably buggy.
     */
    protected function rebaseDir($oldBase, $newBase, $dir)
    {
        if ($this->isAbsolutePath($dir))
            return str_replace($oldBase, $newBase, $dir);

        return $newBase . DIRECTORY_SEPARATOR . $dir;
    }

    protected function getOldRootDir()
    {
        return realpath(getcwd()); // FIXME: Correct way?
    }

    protected function rebaseFromOldRootToNewRoot($dir)
    {
        $oldRootDir = $this->getOldRootDir();
        $newRootDir = $this->getNewRootDir();
        $this->ensureNewRootDirExists();
        $newRootDir = realpath($newRootDir);
        return $this->rebaseDir($oldRootDir, $newRootDir, $dir);
    }

    /**
     * @todo May be broken on Windows.
     */
    protected function isAbsolutePath($file)
    {
        return !empty($file) && $file[0] === '/';
    }

    protected function ensureNewRootDirExists()
    {
        $this->filesystem->ensureDirectoryExists($this->getNewRootDir());
    }

    protected function ensureNewRootVendorDirExists()
    {
        $newVendorDir = $this->rebaseFromOldRootToNewRoot($this->vendorDir);
        $this->filesystem->ensureDirectoryExists($newVendorDir);
    }

}
