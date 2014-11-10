<?php

namespace Viison\ComposerShopwareShopManager;

class RuleValueResult implements RuleResultWithValue {
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
        return false;
    }
}
