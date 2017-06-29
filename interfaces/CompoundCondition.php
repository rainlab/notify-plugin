<?php namespace RainLab\Notify\Interfaces;

/**
 * This contract represents a Compound Condition rule.
 */
interface CompoundCondition
{
    /**
     * Returns the text to use when joining two rules within.
     * @return string
     */
    public function getJoinText();

    /**
     * Returns a list of condition types (`ConditionBase::TYPE_*` constants)
     * that can be added to this compound condition
     */
    public function getAllowedSubtypes();
}
