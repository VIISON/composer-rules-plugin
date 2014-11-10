<?php

namespace Viison\ComposerShopwareShopManager;

use Composer\Package\PackageInterface;
use Composer\Installer\InstallerInterface;
use Composer\Repository\InstalledRepositoryInterface;

class RuleEngine implements Rule {

    const CONFIG_RULE_NAME = 'rule';

    use DebugLog;

    /**
     * @var RuleConfig
     */
    protected $config;

    /**
     * @var RuleFactory
     */
    protected $factory;

    /**
     * @var array int => Rule, corresponding to the config entries.
     */
    protected $instances = array();

    public function __construct(RuleConfig $config, RuleFactory $factory) {
        $this->config = $config;
        $this->factory = $factory;
    }

    public function postInstall(PackageInterface $rootPackage,
        InstalledRepositoryInterface $repo,
        PackageInterface $package,
        InstallerInterface $mainInstaller)
    {
        //$this->logMethod(__METHOD__, array($rootPackage, $repo, $package));
        $result = $this->onEach(function($rule)
            use ($rootPackage, $repo, $package, $mainInstaller)
            {
                $rule->postInstall($rootPackage, $repo, $package,
                    $mainInstaller);
                return new RuleValueResult(null);
            });

        if ($result instanceof RuleResultWithValue)
            return $result->getValue();
    }

    public function canGetInstallPath(PackageInterface $rootPackage,
        PackageInterface $package, InstallerInterface $mainInstaller)
    {
        $result = $this->onEach(function($rule)
            use ($rootPackage, $package, $mainInstaller)
            {
                return $rule->canGetInstallPath($rootPackage, $package, $mainInstaller);
            });

        if ($result instanceof RuleResultWithValue)
            return $result->getValue();
    }

    public function getInstallPath(PackageInterface $rootPackage,
        PackageInterface $package, InstallerInterface $mainInstaller)
    {
        $result = $this->onEach(function($rule)
            use ($rootPackage, $package, $mainInstaller)
            {
                return $rule->getInstallPath($rootPackage, $package, $mainInstaller);
            });

        if ($result instanceof RuleResultWithValue)
            return $result->getValue();
    }

    protected function onEach(Callable $do)
    {
        $rules = $this->config->get();
        foreach ($rules as $ruleId => $ruleConfig) {
            $ruleName = $ruleConfig[static::CONFIG_RULE_NAME];
            if (!isset($this->instances[$ruleId]))
                $this->instances[$ruleId] = $this->factory
                    ->create($ruleName, $ruleConfig);
            $result = $do($this->instances[$ruleId]);

            if (!($result instanceof RuleResult))
                throw new \Exception('A rule needs to return a rule result, '
                    . 'but result = ' . gettype($result) . ' for rule '
                    . $ruleName . '.');

            if ($result->isFinal())
                return $result;
        }

        return $result;
    }

}
