<?php

namespace Viison\ComposerShopwareShopManager;

class RuleFinalResult implements RuleResultWithValue {
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function isFinal()
    {
        return true;
    }
}
