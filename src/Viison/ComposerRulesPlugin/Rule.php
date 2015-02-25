<?php
/**
 * This file is part of VIISON/composer-rules-plugin.
 *
 * Copyright (c) 2014-2015 VIISON GmbH
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

/**
 * Defines the methods a rule can implement. These are called by RuleEngine.
 */
interface Rule
{
    public function postInstall(RuleResult $prevResult, PackageInterface
        $rootPackage,
        InstalledRepositoryInterface $repo,
        PackageInterface $package,
        InstallerInterface $mainInstaller);

    /**
     * @return boolean
     */
    public function canGetInstallPath(PackageInterface $rootPackage,
        PackageInterface $package, InstallerInterface $mainInstaller);

    public function getInstallPath(RuleResult $prevResult,
        PackageInterface $rootPackage,
        PackageInterface $package, InstallerInterface $mainInstaller);
}
