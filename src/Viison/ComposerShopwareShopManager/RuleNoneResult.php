<?php

namespace Viison\ComposerShopwareShopManager;

class RuleNoneResult implements RuleResult {
    public function isFinal()
    {
        return false;
    }
}
