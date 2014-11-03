<?php

namespace Viison\ComposerRulesPlugin;

use Composer\Package\PackageInterface;
use Composer\Installer\InstallerInterface;
use Composer\Repository\InstalledRepositoryInterface;

class RuleEngine {

    const CONFIG_RULE_NAME = 'rule';

    /**
     * @var RuleConfig
     */
    protected $config;

    /**
     * @var RuleFactory
     */
    protected $factory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var array int => Rule, corresponding to the config entries.
     */
    protected $instances = array();

    public function __construct(RuleConfig $config, RuleFactory $factory,
        Logger $logger) {
        $this->config = $config;
        $this->factory = $factory;
        $this->logger = $logger;
    }

    public function postInstall(PackageInterface $rootPackage,
        InstalledRepositoryInterface $repo,
        PackageInterface $package,
        InstallerInterface $mainInstaller)
    {
        $this->logger->logMethod(__METHOD__,
            array($rootPackage, $repo, $package));
        $result = $this->onEach(function($rule, $prevResult)
            use ($rootPackage, $repo, $package, $mainInstaller)
            {
                $rule->postInstall($prevResult, $rootPackage, $repo, $package,
                    $mainInstaller);
                return new RuleValueResult(null);
            });

        if ($result instanceof RuleResultWithValue)
            return $result->getValue();
        else
            throw new \Exception('Not implemented. Result = '
                . json_encode($result));
    }

    public function getInstallPath(PackageInterface $rootPackage,
        PackageInterface $package, InstallerInterface $mainInstaller)
    {
        $result = $this->onEach(function($rule, $prevResult)
            use ($rootPackage, $package, $mainInstaller)
            {
                if ($rule->canGetInstallPath($rootPackage, $package, $mainInstaller))
                    return $rule->getInstallPath($prevResult, $rootPackage, $package, $mainInstaller);
                else
                    return $prevResult;
            });

        if ($result instanceof RuleResultWithValue)
            return $result->getValue();
        elseif ($result instanceof RuleNoneResult)
            return null;
        else
            throw new \Exception('Not implemented. Result = '
                . json_encode($result));
    }

    protected function onEach(Callable $do)
    {
        $rules = $this->config->get();
        $result = new RuleNoneResult();
        foreach ($rules as $ruleId => $ruleConfig) {
            $ruleName = $ruleConfig[static::CONFIG_RULE_NAME];
            if (!isset($this->instances[$ruleId]))
                $this->instances[$ruleId] = $this->factory
                    ->create($ruleName, $ruleConfig);
            $prevResult = $result;
            $result = $do($this->instances[$ruleId], $prevResult);

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
