<?php namespace RainLab\Notify\Classes;

use Db;
use App;
use SystemException;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ModelAttributesConditionBase extends ConditionBase
{
    /**
     * @var string Special value to declare nothing provided.
     */
    const NO_EVAL_VALUE = '__no_eval_value__';

    protected $modelClass = null;

    protected $operators = [
        'is' => 'is',
        'is_not' => 'is not',
        'equals_or_greater' => 'equals or greater than',
        'equals_or_less' => 'equals or less than',
        'contains' => 'contains',
        'does_not_contain' => 'does not contain',
        'greater' => 'greater than',
        'less' => 'less than',
        'one_of' => 'is one of',
        'not_one_of' => 'is not one of'
    ];

    protected $modelObj = null;
    protected $referenceInfo = null;
    protected $modelAttributes = null;

    protected static $modelObjCache = [];
    protected static $attributeControlTypeCache = [];

    public function __construct($host = null)
    {
        parent::__construct($host);

        /*
         * This is used as a base class, so register view path from here too
         */
        $this->addViewPath($this->guessViewPathFrom(__CLASS__));
    }

    public function initConfigData($host)
    {
        $host->operator = 'is';
    }

    public function setCustomData()
    {
        $this->host->condition_control_type = $this->evalControlType();
    }

    //
    // Definitions
    //

    /**
     * This function should return one of the `ConditionBase::TYPE_*` constants 
     * depending on a place where the condition is valid
     */
    public function getConditionType()
    {
        return ConditionBase::TYPE_LOCAL;
    }

    public function defineModelAttributes($type = null)
    {
        return 'attributes.yaml';
    }

    public function defineValidationRules()
    {
        return [
            'value' => 'required'
        ];
    }

    public function defineFormFields()
    {
        return plugins_path('rainlab/notify/classes/modelattributesconditionbase/fields.yaml');
    }

    //
    // Text helpers
    //

    public function getText()
    {
        $host = $this->host;
        $attributes = $this->listModelAttributes();

        if (isset($attributes[$host->subcondition])) {
            $result = $this->getConditionTextPrefix($host, $attributes);
        }
        else {
            $result = 'Unknown attribute';
        }

        $result .= ' <span class="operator">'.array_get($this->operators, $host->operator, $host->operator).'</span> ';

        $controlType = $this->getValueControlType();

        if ($controlType == 'text') {
            $result .= $host->value;
        }
        else {
            $textValue = $this->getCustomTextValue();
            if ($textValue !== false) {
                return $result.' '.$textValue;
            }

            $referenceInfo = $this->prepareReferenceListInfo();
            $modelObj = $referenceInfo->referenceModel;

            if (!count($referenceInfo->columns)) {
                return $result;
            }

            if (!strlen($host->value)) {
                return $result .= '?';
            }

            $visibleField = $referenceInfo->primaryColumn;

            if ($controlType == 'dropdown') {
                $obj = $modelObj->where('id', $host->value)->first();
                if ($obj) {
                    $result .= e($obj->{$visibleField});
                }
            }
            else {
                $ids = explode(',', $host->value);
                foreach ($ids as &$id) {
                    $id = trim(e($id));
                }

                $records = $modelObj
                    ->whereIn('id', $ids)
                    ->orderBy($visibleField)
                    ->get();

                $recordNames = [];
                foreach ($records as $record) {
                    $recordNames[] = $record->{$visibleField};
                }

                $result .= '('.implode(', ', $recordNames).')';
            }
        }

        return $result;
    }

    protected function getConditionTextPrefix($parametersHost, $attributes)
    {
        return $attributes[$parametersHost->subcondition];
    }

    public function getCustomTextValue()
    {
        return false;
    }

    //
    // Options
    //

    public function getSubconditionOptions()
    {
        return $this->listModelAttributes();
    }

    public function getOperatorOptions()
    {
        $hostObj = $this->host;
        $options = [];
        $attribute = $hostObj->subcondition;

        $currentOperatorValue = $hostObj->operator;

        $model = $this->getModelObj();
        $definitions = $this->listModelAttributeInfo();

        if (!isset($definitions[$attribute])) {
            $options = ['none' => 'Unknown attribute selected'];
        }
        else {
            $columnType = array_get($definitions[$attribute], 'type');

            if ($columnType != ConditionBase::CAST_RELATION) {
                if ($columnType == ConditionBase::CAST_STRING) {
                    $options = [
                        'is' => 'is',
                        'is_not' => 'is not',
                        'contains' => 'contains',
                        'does_not_contain' => 'does not contain'
                    ];
                }
                else {
                    $options = [
                        'is' => 'is',
                        'is_not' => 'is not',
                        'equals_or_greater' => 'equals or greater than',
                        'equals_or_less' => 'equals or less than',
                        'greater' => 'greater than',
                        'less' => 'less than'
                    ];
                }
            }
            else {
                $options = [
                    'is' => 'is',
                    'is_not' => 'is not',
                    'one_of' => 'is one of',
                    'not_one_of' => 'is not one of'
                ];
            }
        }

        if (!array_key_exists($currentOperatorValue, $options)) {
            $keys = array_keys($options);
            if (count($keys)) {
                $hostObj->operator = $options[$keys[0]];
            }
            else {
                $hostObj->operator = null;
            }
        }

        return $options;
    }

    public function getValueDropdownOptions()
    {
        $hostObj = $this->host;
        $attribute = $hostObj->subcondition;
        $definitions = $this->listModelAttributeInfo();

        if (!isset($definitions[$attribute])) {
            return [];
        }

        $columnType = array_get($definitions[$attribute], 'type');

        if ($columnType != ConditionBase::CAST_RELATION) {
            return [];
        }

        $referenceInfo = $this->prepareReferenceListInfo();
        $referenceModel = $referenceInfo->referenceModel;
        $nameFrom = $referenceInfo->primaryColumn;
        $keyFrom = $referenceInfo->primaryKey;

        // Determine if the model uses a tree trait
        $treeTraits = ['October\Rain\Database\Traits\NestedTree', 'October\Rain\Database\Traits\SimpleTree'];
        $usesTree = count(array_intersect($treeTraits, class_uses($referenceModel))) > 0;

        $results = $referenceModel->get();

        return $usesTree
            ? $results->listsNested($nameFrom, $keyFrom)
            : $results->lists($nameFrom, $keyFrom);
    }

    //
    // Control type
    //

    protected function evalControlType()
    {
        $hostObj = $this->host;
        $attribute = $hostObj->subcondition;
        $operator = $hostObj->operator;

        $definitions = $this->listModelAttributeInfo();

        if (!isset($definitions[$attribute])) {
            return 'text';
        }

        $columnType = array_get($definitions[$attribute], 'type');

        if ($columnType != ConditionBase::CAST_RELATION) {
            return 'text';
        }
        else {
            if ($operator == 'is' || $operator == 'is_not') {
                return 'dropdown';
            }

            return 'multi_value';
        }
    }

    public function getValueControlType()
    {
        if (App::runningInBackend()) {
            return $this->evalControlType();
        }

        $hostObj = $this->host;

        if ($controlType = $hostObj->condition_control_type) {
            return $controlType;
        }

        $controlType = $this->evalControlType();

        $this->getModelObj()
            ->where('id', $host->id)
            ->update(['condition_control_type' => $controlType]);

        return $hostObj->condition_control_type = $controlType;
    }

    //
    // Attributes
    //

    public function listSubconditions()
    {
        $attributes = $this->listModelAttributes();

        $result = [];

        foreach ($attributes as $name => $code) {
            $result[$code] = $name;
        }

        return $result;
    }

    /**
     * Returns the supported attributes by a condition as an array.
     * The key is the attribute and the value is the label.
     *
     * @return array
     */
    protected function listModelAttributes()
    {
        $attributeInfo = $this->listModelAttributeInfo();

        foreach ($attributeInfo as $attribute => $info) {
            $attributes[$attribute] = array_get($info, 'label');
        }

        asort($attributes);

        return $attributes;
    }

    protected function listModelAttributeInfo()
    {
        if ($this->modelAttributes) {
            return $this->modelAttributes;
        }

        $config = $this->makeConfig($this->defineModelAttributes($this->getConditionType()));

        $attributes = $config->attributes ?? [];

        /*
         * Set defaults
         */
        foreach ($attributes as $attribute => $info) {
            if (!isset($info['type'])) {
                $attributes[$attribute]['type'] = 'string';
            }
        }

        return $this->modelAttributes = $attributes;
    }

    //
    // Relation based attributes
    //

    public function getReferencePrimaryColumn($record)
    {
        $referenceInfo = $this->prepareReferenceListInfo();

         return $record->{$referenceInfo->primaryColumn};
    }

    public function listSelectedReferenceRecords()
    {
        $referenceInfo = $this->prepareReferenceListInfo();
        $model = $referenceInfo->referenceModel;

        $value = $this->host->value;
        $keys = strlen($value) ? explode(',', $value) : [];

        if (count($keys)) {
            $model = $model->whereIn('id', $keys);
        }
        else {
            $model = $model->whereRaw('id <> id');
        }

        $orderField = $referenceInfo->primaryColumn;

        return $model->orderBy($orderField)->get();
    }

    public function prepareReferenceListInfo()
    {
        if (!is_null($this->referenceInfo)) {
            return $this->referenceInfo;
        }

        $model = $this->getModelObj();
        $attribute = $this->host->subcondition;
        $definitions = $this->listModelAttributeInfo();
        $definition = array_get($definitions, $attribute);

        $columns = array_get($definition, 'columns');
        $primaryColumn = array_get($definition, 'relation.nameFrom', 'name');

        if ($model->hasRelation($attribute)) {
            $relationType = $model->getRelationType($attribute);
            $relationModel = $model->makeRelation($attribute);
            $relationObject = $model->{$attribute}();

            // Some simpler relations can specify a custom local or foreign "other" key,
            // which can be detected and implemented here automagically.
            $primaryKey = in_array($relationType, ['hasMany', 'belongsTo', 'hasOne'])
                ? $relationObject->getOtherKey()
                : $relationModel->getKeyName();
        }
        elseif ($relationClass = array_get($definition, 'relation.model')) {
            $relationModel = new $relationClass;
            $primaryKey = array_get($definition, 'relation.keyFrom', 'id');
        }
        else {
            throw new SystemException(sprintf('Model %s does not contain a relation "%s"', get_class($model), $attribute));
        }

        if (!$columns) {
            $columns = [
                $primaryColumn => [
                    'label' => array_get($definition, 'relation.label', '?'),
                    'searchable' => true
                ]
            ];
        }

        $this->referenceInfo = [];
        $this->referenceInfo['referenceModel'] = $relationModel;
        $this->referenceInfo['primaryKey'] = $primaryKey;
        $this->referenceInfo['primaryColumn'] = $primaryColumn;
        $this->referenceInfo['columns'] = $columns;

        return $this->referenceInfo = (object) $this->referenceInfo;
    }

    public function onPreRender($controller, $widget)
    {
        $controlType = $this->getValueControlType();

        if ($controlType != 'multi_value') {
            return;
        }

        $selectionColumn = [
            '_select_record' => [
                'label' => '',
                'sortable' => false,
                'type' => 'partial',
                'width' => '10px',
                'path' => plugins_path('rainlab/notify/classes/modelattributesconditionbase/_column_select_record.htm')
            ]
        ];

        $referenceInfo = $this->prepareReferenceListInfo();
        $filterModel = $referenceInfo->referenceModel;
        $filterColumns = $selectionColumn + $referenceInfo->columns;

        /*
         * List widget
         */
        $config = $this->makeConfig();
        $config->columns = $filterColumns;
        $config->model = $filterModel;
        $config->alias = $widget->alias . 'List';
        $config->showSetup = false;
        $config->showCheckboxes = false;
        $config->recordsPerPage = 6;
        $listWidget = $controller->makeWidget('Backend\Widgets\Lists', $config);

        /*
         * Search widget
         */
        $config = $this->makeConfig();
        $config->alias = $widget->alias . 'Search';
        $config->growable = false;
        $config->prompt = 'backend::lang.list.search_prompt';
        $searchWidget = $controller->makeWidget('Backend\Widgets\Search', $config);
        $searchWidget->cssClasses[] = 'condition-filter-search';

        $listWidget->bindToController();
        $searchWidget->bindToController();

        /*
         * Extend list query
         */
        $listWidget->bindEvent('list.extendQueryBefore', function ($query) use ($filterModel) {
            $this->prepareFilterQuery($query, $filterModel);
        });

        /*
         * Link the Search Widget to the List Widget
         */
        $listWidget->setSearchTerm($searchWidget->getActiveTerm());

        $searchWidget->bindEvent('search.submit', function () use (&$searchWidget, $listWidget) {
            $listWidget->setSearchTerm($searchWidget->getActiveTerm());
            return $listWidget->onRefresh();
        });

        $controller->vars['listWidget'] = $listWidget;
        $controller->vars['searchWidget'] = $searchWidget;
        $controller->vars['filterHostModel'] = $this->host;
    }

    public function prepareFilterQuery($query, $model)
    {
    }

    //
    // Condition check
    //

    /**
     * Checks whether the condition is TRUE for a specified model
     * @return bool
     */
    public function evalIsTrue($model, $customValue = '__no_eval_value__')
    {
        $hostObj = $this->host;

        $operator = $hostObj->operator;
        $attribute = $hostObj->subcondition;

        $conditionValue = $hostObj->value;
        $conditionValue = trim(mb_strtolower($conditionValue));

        $controlType = $this->getValueControlType();

        if ($controlType == 'text') {
            if ($customValue === self::NO_EVAL_VALUE) {
                $modelValue = trim(mb_strtolower($model->{$attribute}));
            }
            else {
                $modelValue = trim(mb_strtolower($customValue));
            }

            if ($operator == 'is') {
                return $modelValue == $conditionValue;
            }

            if ($operator == 'is_not') {
                return $modelValue != $conditionValue;
            }

            if ($operator == 'contains') {
                return mb_strpos($modelValue, $conditionValue) !== false;
            }

            if ($operator == 'does_not_contain') {
                return mb_strpos($modelValue, $conditionValue) === false;
            }

            if ($operator == 'equals_or_greater') {
                return $modelValue >= $conditionValue;
            }

            if ($operator == 'equals_or_less') {
                return $modelValue <= $conditionValue;
            }

            if ($operator == 'greater') {
                return $modelValue > $conditionValue;
            }

            if ($operator == 'less') {
                return $modelValue < $conditionValue;
            }
        }

        if ($controlType == 'dropdown') {
            if ($customValue === self::NO_EVAL_VALUE) {
                $modelValue = $model->{$attribute};
            }
            else {
                $modelValue = $customValue;
            }

            if ($operator == 'is') {
                if ($modelValue == null) {
                    return false;
                }

                if ($modelValue instanceof EloquentModel) {
                    return $modelValue->getKey() == $conditionValue;
                }

                if (
                    is_array($modelValue) &&
                    count($modelValue) == 1 &&
                    array_key_exists(0, $modelValue)
                ) {
                    return $modelValue[0] == $conditionValue;
                }

                if ($modelValue instanceof EloquentCollection) {
                    if ($modelValue->count() != 1) {
                        return false;
                    }

                    return $modelValue[0]->getKey() == $conditionValue;
                }
            }

            if ($operator == 'is_not') {
                if ($modelValue == null) {
                    return true;
                }

                if ($modelValue instanceof EloquentModel) {
                    return $modelValue->getKey() != $conditionValue;
                }

                if (is_array($modelValue)) {
                    if (count($modelValue) != 1) {
                        return true;
                    }

                    if (!array_key_exists(0, $modelValue)) {
                        return true;
                    }

                    return $modelValue[0] != $conditionValue;
                }

                if ($modelValue instanceof EloquentCollection) {
                    if (!$modelValue->count() || $modelValue->count() > 1) {
                        return true;
                    }

                    return $modelValue->first()->getKey() != $conditionValue;
                }
            }
        }

        if ($controlType == 'multi_value') {
            if ($customValue === self::NO_EVAL_VALUE) {
                $modelValue = $model->{$attribute};
            }
            else {
                $modelValue = $customValue;
            }

            if (
                (!$modelValue instanceof EloquentCollection) &&
                (!$modelValue instanceof EloquentModel) &&
                !is_array($modelValue)
            ) {
                return false;
            }

            if (strlen($conditionValue)) {
                $conditionValues = explode(',', $conditionValue);
                foreach ($conditionValues as &$value) {
                    $value = trim($value);
                }
            } else {
                $conditionValues = [];
            }

            if ($modelValue instanceof EloquentCollection) {
                $modelKeys = array_keys($modelValue->lists('id', 'id'));
            }
            elseif ($modelValue instanceof EloquentModel) {
                $modelKeys = [$modelValue->getKey()];
            }
            else {
                $modelKeys = $modelValue;
            }

            if ($operator == 'is') {
                $operator = 'one_of';
            }
            elseif ($operator == 'is_not') {
                $operator = 'not_one_of';
            }

            if ($operator == 'one_of') {
                return count(array_intersect($conditionValues, $modelKeys)) ? true : false;
            }

            if ($operator == 'not_one_of') {
                return count(array_intersect($conditionValues, $modelKeys)) ? false : true;
            }
        }

        return false;
    }

    //
    // Helpers
    //

    public function getModelObj()
    {
        if ($this->modelObj === null) {
            if (array_key_exists($this->modelClass, self::$modelObjCache)) {
                $this->modelObj = self::$modelObjCache[$this->modelClass];
            }
            else {
                $this->modelObj = self::$modelObjCache[$this->modelClass] = new $this->modelClass;
            }
        }

        return $this->modelObj;
    }
}
