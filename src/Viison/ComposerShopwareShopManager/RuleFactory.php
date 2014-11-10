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

    /**
     * @var RepositoryManager
     */
    protected $repositoryManager;

    public function __construct(
        InstallationManager $installationManager,
        RepositoryManager $repositoryManager)
    {
        $this->installationManager = $installationManager;
        $this->map = array(
            'rule-symlink-deps-of-deps' => function($args)
                use ($installationManager, $repositoryManager)
            {
                return new RuleSymlinkDepsOfDeps($args, $installationManager,
                    $repositoryManager);
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
