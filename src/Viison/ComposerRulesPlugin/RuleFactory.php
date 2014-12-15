<?php

namespace Viison\ComposerRulesPlugin;

use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Util\Filesystem;

class RuleFactory
{
    /**
     * @var array rule name => (params) -> Rule
     */
    protected $map;

    public function __construct(
        Composer $composer, IOInterface $io, Filesystem $filesystem,
        Logger $logger)
    {
        $this->map = array(
            'rule-symlink-deps-of-deps' => function ($args) use ($composer, $io, $filesystem, $logger) {
                $logger->logMethod(
                    'RuleFactory::__construct:'.__LINE__,
                    array('Creating RuleSymlinkDepsOfDeps.'));

                return new RuleSymlinkDepsOfDeps(
                    $args, $composer, $io, $filesystem);
            },
            'rule-add-installer' => function ($args) use ($composer, $io, $filesystem, $logger) {
                $logger->logMethod(
                    'RuleFactory::__construct:'.__LINE__,
                    array('Creating RuleAddInstaller.'));

                return new RuleAddInstaller(
                    $args, $composer, $io, $filesystem);
            },
        );
    }

    public function create($ruleName, array $params)
    {
        if (!isset($this->map[$ruleName])) {
            throw new \Exception('No rule with name \''
            .addslashes($ruleName).'\'');
        }

        return $this->map[$ruleName]($params);
    }
}
