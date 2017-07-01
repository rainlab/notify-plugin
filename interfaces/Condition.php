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
     *
     * Options can contain these keys:
     * - ruleType: Rule type as specified by ConditionBase::TYPE_* constants
     * - parentIds: An array of parent ids to constrain child conditions
     * - extraRules: An array of additional condition classes to use
     *
     * @param array $options
     * @return array
     */
    public function getChildOptions(array $options);
}
