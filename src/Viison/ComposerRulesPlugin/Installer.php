<?php

namespace Viison\ComposerRulesPlugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;

class Installer extends LibraryInstaller {

    use DebugLog;

    const CONFIG_VIISON_INSTALLER_KEY = 'composer-rules-plugin';
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
        $this->logMethod(__METHOD__, array());
        echo '#######################################################', "\n\n";
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

    public function update(InstalledRepositoryInterface $repo,
        PackageInterface $initial, PackageInterface $target)
    {
        $this->logMethod(__METHOD__, array($repo, $initial, $target));
        return parent::update($repo, $initial, $target);
    }

    public function uninstall(InstalledRepositoryInterface $repo,
        PackageInterface $package)
    {
        $this->logMethod(__METHOD__, array($repo, $package));
        return parent::uninstall($repo, $package);
    }

    protected function installCode(PackageInterface $package)
    {
        $this->logMethod(__METHOD__, array($package));
        return parent::installCode($package);
    }

    protected function updateCode(PackageInterface $initial,
        PackageInterface $target)
    {
        $this->logMethod(__METHOD__, array($initial, $target));
        return parent::updateCode($initial, $target);
    }

    protected function removeCode(PackageInterface $package)
    {
        $this->logMethod(__METHOD__, array($package));
        return parent::removeCode($package);
    }

    public function getInstallPath(PackageInterface $package)
    {
        $this->logMethod(__METHOD__, array($package));
        //$e = new \Exception();
        //echo str_replace("\n", "        \n", $e), "\n\n\n";

        $ruleEngine = $this->getRuleEngine();

        $installPath = $ruleEngine->getInstallPath(
            $this->getRootPackage(),
            $package,
            $this);

        if (!isset($installPath)) {
            $installPath = parent::getInstallPath($package);
            echo "     ->   ordinary installPath = $installPath\n";
        } else {
            echo "     -> ruleEngine installPath = $installPath\n";
        }

        return $installPath;
    }

}
