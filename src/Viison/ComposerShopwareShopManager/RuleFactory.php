<?php

namespace Viison\ComposerShopwareShopManager;

use Composer\Installer\InstallationManager;
use Composer\Repository\RepositoryManager;

use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Util\Filesystem;

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
        Composer $composer, IOInterface $io, Filesystem $filesystem)
    {
        $this->map = array(
            'rule-symlink-deps-of-deps' => function($args)
                use ($composer)
                {
                    return new RuleSymlinkDepsOfDeps(
                        $args,
                        $composer->getInstallationManager(),
                        $composer->getRepositoryManager());
                },
            'rule-add-installer' => function($args)
                use ($composer, $io, $filesystem)
                {
                    return new RuleAddInstaller(
                        $args,
                        $composer,
                        $io,
                        $filesystem);
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
