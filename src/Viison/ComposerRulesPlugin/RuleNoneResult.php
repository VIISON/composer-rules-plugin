<?php

namespace Viison\ComposerRulesPlugin;

class RuleNoneResult implements RuleResult {
    public function isFinal()
    {
        return false;
    }
}
