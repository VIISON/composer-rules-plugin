<?php

namespace Viison\ComposerRulesPlugin;

use Composer\Package\PackageInterface;
use Composer\Repository\WritableRepositoryInterface;

trait DebugLog {

    protected function logMethodStep($method, array $args = array()) {
        return str_replace("\n", "\n    ", $this->logMethod($method, $args));
    }

    protected function logMethod($method, array $args = array())
    {
        $args = array_map(function($item) {
            if ($item instanceof PackageInterface) {
                $package = $item;
                $pkgInfo = new \stdclass;
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

        echo "\n\n\n\n\n", $method, '(',
            str_replace("\n", "\n        ",
                json_encode($args,  JSON_PRETTY_PRINT)), ')',
            ":\n";
    }

}
