<?php

namespace Viison\ComposerRulesPlugin;

class RuleNoRulesResult implements RuleResult
{
    public function isFinal()
    {
        return true;
    }
}
