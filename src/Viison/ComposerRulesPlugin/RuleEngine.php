<?php
/**
 * VIISON/composer-rules-plugin
 *
 * Copyright (c) 2014 VIISON GmbH
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * @license MIT <http://opensource.org/licenses/MIT>
 */

namespace Viison\ComposerRulesPlugin;

use Composer\Package\PackageInterface;
use Composer\Installer\InstallerInterface;
use Composer\Repository\InstalledRepositoryInterface;

class RuleEngine
{
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
        Logger $logger)
    {
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
        $result = $this->onEach(function ($rule, $prevResult) use ($rootPackage, $repo, $package, $mainInstaller) {
                $rule->postInstall($prevResult, $rootPackage, $repo, $package,
                    $mainInstaller);

                return new RuleValueResult(null);
            });

        if ($result instanceof RuleResultWithValue) {
            return $result->getValue();
        } elseif ($result instanceof RuleNoRulesResult) {
            return;
        } else {
            throw new \Exception('Not implemented. Result = '
                .json_encode($result));
        }
    }

    public function getInstallPath(PackageInterface $rootPackage,
        PackageInterface $package, InstallerInterface $mainInstaller)
    {
        $result = $this->onEach(function ($rule, $prevResult) use ($rootPackage, $package, $mainInstaller) {
                if ($rule->canGetInstallPath($rootPackage, $package, $mainInstaller)) {
                    return $rule->getInstallPath($prevResult, $rootPackage, $package, $mainInstaller);
                } else {
                    return $prevResult;
                }
            });

        if ($result instanceof RuleResultWithValue) {
            return $result->getValue();
        } elseif ($result instanceof RuleNoneResult) {
            return;
        } elseif ($result instanceof RuleNoRulesResult) {
            return;
        } else {
            throw new \Exception('Not implemented. Result = '
                .json_encode($result));
        }
    }

    protected function onEach(callable $do)
    {
        $rules = $this->config->get();
        if (empty($rules)) {
            return new RuleNoRulesResult();
        }

        $result = new RuleNoneResult();
        foreach ($rules as $ruleId => $ruleConfig) {
            $ruleName = $ruleConfig[static::CONFIG_RULE_NAME];
            if (!isset($this->instances[$ruleId])) {
                $this->instances[$ruleId] = $this->factory
                    ->create($ruleName, $ruleConfig);
            }
            $prevResult = $result;
            $result = $do($this->instances[$ruleId], $prevResult);

            if (!($result instanceof RuleResult)) {
                throw new \Exception('A rule needs to return a rule result, '
                    .'but result = '.gettype($result).' for rule '
                    .$ruleName.'.');
            }

            if ($result->isFinal()) {
                return $result;
            }
        }

        return $result;
    }
}
