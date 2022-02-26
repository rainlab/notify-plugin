<?php namespace RainLab\Notify\NotifyRules;

use Cms\Classes\Theme;
use RainLab\Notify\Classes\ConditionBase;

class ExecutionContextCondition extends ConditionBase
{
    protected $operators = [
        'is' => 'is',
        'is_not' => 'is not',
    ];

    public function getName()
    {
        return 'Event is triggered from environment';
    }

    public function getTitle()
    {
        return 'Execution context';
    }

    public function getText()
    {
        $host = $this->host;
        $value = $host->value;
        $attribute = $host->subcondition;
        $subconditions = $this->getSubconditionOptions();

        $result = array_get($subconditions, $attribute, 'Execution context');
        $result .= ' <span class="operator">'.array_get($this->operators, $host->operator, $host->operator).'</span> ';

        if ($attribute == 'locale' || $attribute == 'environment') {
            $result .= strtolower($value) ?: '?';
        }
        elseif ($value) {
            $options = $this->getValueOptions();
            $result .= strtolower(array_get($options, $value));
        }
        else {
            $result .= '?';
        }

        return $result;
    }

    /**
     * Returns a title to use for grouping subconditions
     * in the Create Condition drop-down menu
     */
    public function getGroupingTitle()
    {
        return 'Execution context';
    }

    public function listSubconditions()
    {
        return array_flip($this->getSubconditionOptions());
    }

    public function initConfigData($host)
    {
        $host->operator = 'is';
    }

    public function setFormFields($fields)
    {
        $attribute = $fields->subcondition->value;

        if ($attribute == 'locale' || $attribute == 'environment') {
            $fields->value->type = 'text';
        }
        else {
            $fields->value->type = 'dropdown';
        }
    }

    public function getValueOptions()
    {
        $attribute = $this->host->subcondition;
        $result = [];

        if ($attribute == 'context') {
            $result = [
                'backend' => 'Back-end area',
                'front' => 'Front-end website',
                'console' => 'Command line interface',
            ];
        }

        if ($attribute == 'theme') {
            foreach (Theme::all() as $theme) {
                $result[$theme->getDirName()] = $theme->getDirName();
            }
        }

        return $result;
    }

    public function getSubconditionOptions()
    {
        return [
            'environment' => 'Application environment',
            'context'     => 'Request context',
            'theme'       => 'Active theme',
            'locale'      => 'Visitor locale',
        ];
    }

    public function getOperatorOptions()
    {
        return $this->operators;
    }

    /**
     * Checks whether the condition is TRUE for specified parameters
     * @param array $params
     * @return bool
     */
    public function isTrue(&$params)
    {
        $hostObj = $this->host;
        $attribute = $hostObj->subcondition;

        $conditionValue = $hostObj->value;
        $conditionValue = trim(mb_strtolower($conditionValue));

        if ($attribute == 'locale') {
            return array_get($params, 'appLocale') == $conditionValue;
        } else if ($attribute === 'environment') {
            return $conditionValue === \App::environment();
        }

        return false;
    }
}
