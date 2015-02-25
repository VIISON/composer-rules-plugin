<?php
/**
 * This file is part of VIISON/composer-rules-plugin.
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
