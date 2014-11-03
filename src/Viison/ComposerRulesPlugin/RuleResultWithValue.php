<?php

namespace Viison\ComposerRulesPlugin;

interface RuleResultWithValue extends RuleResult {

    public function getValue();

}
