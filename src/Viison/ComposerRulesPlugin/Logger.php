<?php

namespace Viison\ComposerRulesPlugin;

use Composer\Package\PackageInterface;
use Composer\IO\IOInterface;
use Composer\Repository\WritableRepositoryInterface;

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
