<?php

namespace Viison\ComposerShopwareShopManager;

use Composer\Installer\InstallationManager;

class RuleFactory {

    /**
     * @var array rule name => (params) -> Rule
     */
    protected $map;

    /**
     * @var InstallationManager
     */
    protected $installationManager;

    public function __construct(InstallationManager $installationManager)
    {
        $this->installationManager = $installationManager;
        $this->map = array(
            'rule-symlink-deps-of-deps' => function($args) use ($installationManager) {
                return new RuleSymlinkDepsOfDeps($args, $installationManager);
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
