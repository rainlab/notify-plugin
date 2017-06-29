<?php namespace RainLab\Notify\Interfaces;

/**
 * This contract represents a Compound Condition rule.
 */
interface Condition
{
    /**
     * Returns a condition text summary when displaying to the user.
     * @return string
     */
    public function getText();

    /**
     * Returns a condition title for displaying in the condition settings form
     * @return string
     */
    public function getTitle();

    /**
     * Returns a list of options supported beneth this condition.
     * @param string $ruleType
     * @param array $parentIds
     * @return array
     */
    public function getChildOptions($ruleType, array $parentIds);
}
