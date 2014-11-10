<?php

namespace Viison\ComposerShopwareShopManager;

class RuleConfig {

    /**
     * @var array The extra.vision-installer.rules configuration array.
     */
    private $ruleConfig;

    public function __construct(array $ruleConfig = array())
    {
        $this->set($ruleConfig);
    }

    public function set(array $ruleConfig)
    {
        $this->validate($ruleConfig);
        $this->ruleConfig = $ruleConfig;
    }

    protected function validate(array $ruleConfig)
    {
    }

    public function get()
    {
        return $this->ruleConfig;
    }

}
