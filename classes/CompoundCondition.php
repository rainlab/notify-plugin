<?php namespace RainLab\Notify\Classes;

use RainLab\Notify\Interfaces\CompoundCondition as CompoundConditionInterface;

/**
 * Compound Condition Class
 */
class CompoundCondition extends ConditionBase implements CompoundConditionInterface
{
    /**
     * Returns a condition title for displaying in the condition settings form
     * @return string
     */
    public function getTitle()
    {
        return 'Compound condition';
    }

    public function getText()
    {
        $result = $this->host->condition_type == 0
            ? 'ALL of subconditions should be '
            : 'ANY of subconditions should be ';

        $result .= $this->host->condition == 'false' ? 'FALSE' : 'TRUE';

        return $result;
    }

    /**
     * Returns the text to use when joining two rules within.
     * @return string
     */
    public function getJoinText()
    {
        return $this->host->condition_type == 0 ? 'AND' : 'OR';
    }

    /**
     * Returns a list of condition types (`ConditionBase::TYPE_*` constants)
     * that can be added to this compound condition
     */
    public function getAllowedSubtypes()
    {
        return [];
    }

    public function defineFormFields()
    {
        return 'fields.yaml';
    }

    public function initConfigData($host)
    {
        $host->condition_type = 0;
        $host->condition = 'true';
    }

    public function getConditionOptions()
    {
        $options = [
            'true' => 'TRUE',
            'false' => 'FALSE'
        ];

        return $options;
    }

    public function getConditionTypeOptions()
    {
        $options = [
            '0' => 'ALL subconditions should meet the requirement',
            '1' => 'ANY subconditions should meet the requirement'
        ];

        return $options;
    }

    public function getChildOptions(array $options)
    {
        extract(array_merge([
            'extraRules' => [],
        ], $options));

        $result = [
            'Compound condition' => CompoundCondition::class
        ];

        $classes = $extraRules + self::findConditionsByType(ConditionBase::TYPE_ANY);

        $result = $this->addClassesSubconditions($classes, $result);

        return $result;
    }

    protected function addClassesSubconditions($classes, $list)
    {
        foreach ($classes as $conditionClass => $obj) {

            $subConditions = $obj->listSubconditions();

            if ($subConditions) {
                $groupName = $obj->getGroupingTitle();

                foreach ($subConditions as $name => $subcondition) {
                    if (!$groupName) {
                        $list[$name] = $conditionClass.':'.$subcondition;
                    }
                    else {
                        if (!array_key_exists($groupName, $list)) {
                            $list[$groupName] = [];
                        }

                        $list[$groupName][$name] = $conditionClass.':'.$subcondition;
                    }
                }
            }
            else {
                $list[$obj->getName()] = $conditionClass;
            }
        }

        return $list;
    }

    /**
     * Checks whether the condition is TRUE for specified parameters.
     *
     * @param array $params
     * @return bool
     */
    public function isTrue(&$params)
    {
        $hostObj = $this->host;

        $requiredConditionValue = $hostObj->condition == 'true' ? true : false;

        foreach ($hostObj->children as $subcondition) {
            $subconditionResult = $subcondition->getConditionObject()->isTrue($params) ? true : false;

            /*
             * All
             */
            if ($hostObj->condition_type == 0) {
                if ($subconditionResult !== $requiredConditionValue) {
                    return false;
                }

            }
            /*
             * Any
             */
            else {
                if ($subconditionResult === $requiredConditionValue) {
                    return true;
                }
            }
        }

        /*
         * All
         */
        if ($hostObj->condition_type == 0) {
            return true;
        }

        return false;
    }
}
