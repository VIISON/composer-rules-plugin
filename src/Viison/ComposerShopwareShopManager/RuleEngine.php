<?php

namespace Viison\ComposerShopwareShopManager;

use Composer\Installer\InstallerInterface;

class RuleEngine implements InstallerInterface {

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
        PackageInterface $package)
    {
        $this->logMethod(__METHOD__, array($rootPackage, $repo, $package));
        $this->onEach(function($rule) {
            $rule->postInstall($rootPackage, $repo, $package);
        });
    }

    protected function onEach(Callable $do)
    {
        $rules = $this->config->get();
        foreach ($rules as $ruleId => $ruleConfig) {
            $ruleName = $ruleConfig[static::CONFIG_RULE_NAME];
            if (!isset($this->instances[$ruleId]))
                $this->instances[$ruleId] = $this->factory
                    ->create($ruleName, $ruleConfig);
            $do($this->instances[$ruleId]);
        }
    }

}
