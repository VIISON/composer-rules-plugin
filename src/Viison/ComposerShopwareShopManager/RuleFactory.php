<?php

namespace Viison\ComposerShopwareShopManager;

class RuleFactory {

    /**
     * @var array rule name => (params) -> Rule
     */
    protected $map;

    public function __construct()
    {
        $this->map = array(
            'rule-symlink-deps-of-deps' => function($args) {
                return new RuleSymlinkDepsOfDeps($args);
            }
        );
    }

    public function create($ruleName, array $params)
    {
        if (!isset($this->map[$ruleName]))
            throw new \Exception('No rule with name \''
            . addslashes($ruleName) . '\'');
        return $this->map[$ruleName]($params);
    }


}
