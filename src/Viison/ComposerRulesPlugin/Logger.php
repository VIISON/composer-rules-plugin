<?php
/**
 * VIISON/composer-rules-plugin
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
use Composer\IO\IOInterface;
use Composer\Repository\WritableRepositoryInterface;

/**
 * A logger using Composer's IOInterface with special support for printing
 * Composer-specific objects such as PackageInterfaces or
 * WritableRepositoryInterfaces.
 */
class Logger
{
    /**
     * @var IOInterface
     */
    protected $io;

    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    public function logMethodStep($method, array $args = array())
    {
        if (!$this->io->isVerbose()) {
            return;
        }

        return str_replace("\n",
            "\n    ",
            $this->buildLogMethod($method, $args));
    }

    public function logMethod($method, array $args = array())
    {
        if (!$this->io->isVerbose()) {
            return;
        }

        return $this->io->write($this->buildLogMethod($method, $args));
    }

    protected function buildLogMethod($method, array $args = array())
    {
        $args = array_map(function ($item) {
            if ($item instanceof PackageInterface) {
                $package = $item;
                $pkgInfo = new \stdclass();
                $pkgInfo->name = $package->getName();
                $pkgInfo->prettyName = $package->getPrettyName();
                $pkgInfo->typ = $package->getType();
                $pkgInfo->targetDir = $package->getTargetDir();
                $pkgInfo->extra = $package->getExtra();

                return $pkgInfo;
            } elseif ($item instanceof WritableRepositoryInterface) {
                return 'WritableRepositoryInterface()';
            }

            return $item;
        }, $args);

        return "\n\n\n\n\n".$method.'('
            .str_replace("\n", "\n        ",
                json_encode($args,  JSON_PRETTY_PRINT)).'):';
    }
}
