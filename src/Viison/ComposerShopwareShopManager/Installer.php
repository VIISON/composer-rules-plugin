<?php

namespace Viison\ComposerShopwareShopManager;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;

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

    protected function matchRulesForPackage()
    {

    }

    protected function getRuleEngine()
    {
        if (isset($this->ruleEngine))
            return $this->ruleEngine;
        $rules = $this->getInstallerRulesConfig();
        $ruleConfig = new RuleConfig($rules);
        return $this->ruleEngine = new RuleEngine($ruleConfig);
    }

    public function install(InstalledRepositoryInterface $repo,
        PackageInterface $package)
    {
        $this->logMethod(__METHOD__, $repo, $package);
        parent::install($repo, $package);
        return $this->getRuleEngine()->postInstall($this->getRootPackage(),
            $repo, $package);
    }

    public function getInstallPath(PackageInterface $package)
    {
        $pkgInfo = new \stdclass;
        $pkgInfo->name = $package->getName();
        $pkgInfo->prettyName = $package->getPrettyName();
        $pkgInfo->typ = $package->getType();
        $pkgInfo->targetDir = $package->getTargetDir();
        $pkgInfo->extra = $package->getExtra();

        $pkgInfo->rules = $this->constructRules($package);

        echo "\n\n\n\n\n", __METHOD__, '(', str_replace("\n", "\n        ", json_encode($pkgInfo,  JSON_PRETTY_PRINT)), ')', ":\n";

        return parent::getInstallPath($package);
        // DEAD:
        //throw new \Exception('not doing anything');
        $pkgInfo = new \stdclass;
        $pkgInfo->name = $package->getName();
        $pkgInfo->prettyName = $package->getPrettyName();
        $pkgInfo->typ = $package->getType();
        $pkgInfo->targetDir = $package->getTargetDir();
        $pkgInfo->extra = $package->getExtra();

        echo "\n\n\n\n\n", __METHOD__, '(', str_replace("\n", "\n        ", json_encode($pkgInfo,  JSON_PRETTY_PRINT)), ')', ":\n";
        $e = new \Exception();
        echo '    Called from: ', str_replace("\n", "\n        ", $e->getTraceAsString()), "\n";
        $e = null;
        /*
        switch ($package->getType()) {
            case 'shopware-backend-plugin':
            //case 'shopware-core-plugin': // FIXME
            case 'shopware-frontend-plugin':
            case 'shopware-theme':
                $frameworkType = 'shopware';
                $shopwareInstaller = new \Composer\Installers\ShopwareInstaller($package, $this->composer);
                $installPath = $shopwareInstaller->getInstallPath($package, $frameworkType);
                $pkgInfo->usingShopwareInstaller = true;
                break;
            default:
                $installPath = parent::getInstallPath($package);
                $pkgInfo->usingShopwareInstaller = false;
                break;
        }*/

        // FIXME: Throws if not set.
        if (isset($asRootPackage) && $package->getName() === $asRootPackage)
            return $installPath = $this->getNewRootDir();

        // FIXME: Throws if not set.
        if (!isset($installPath) && $this->getFallbackInstallerClassName()) {
            echo '    Looking at fallback installer.' . "\n";
            $fallbackInstaller = $this->getFallbackInstaller();
            if ($fallbackInstaller->supports($package->getType())) {
                echo '    fallback installer supports ' . $package->getName() . "\n";
                $installPath = $fallbackInstaller->getInstallPath($package);
                echo "    installPath set by Fallback ", $this->getFallbackInstallerClassName(), "\n";
            } else {
                echo '    fallback installer doesn\'t support ' . $package->getName() . "!\n";
            }
        }

        if (!isset($installPath))
            $installPath = parent::getInstallPath($package);

        echo "       orig installPath = $installPath\n";

        //$installPath = $this->rebaseFromOldRootToNewRoot($installPath);

        echo "    rebased installPath = $installPath\n";

        $this->ensureNewRootVendorDirExists();

        if (is_dir($installPath)) {
            echo "    installPath exists.\n";
        } else {
            echo "    installPath doesn't exist.\n";
        }

        return $installPath;
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
