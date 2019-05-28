<?php namespace RainLab\Notify\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use RainLab\Notify\Classes\ConditionBase;
use ApplicationException;
use ValidationException;
use Exception;
use Request;

/**
 * Condition builder
 */
class ConditionBuilder extends FormWidgetBase
{
    use \Backend\Traits\FormModelWidget;
    use \Backend\Traits\CollapsableWidget;

    //
    // Configurable properties
    //

    /**
     * @var string Rule type.
     */
    public $conditionsRuleType = ConditionBase::TYPE_ANY;

    //
    // Object properties
    //

    /**
     * {@inheritDoc}
     */
    public $defaultAlias = 'ruleconditions';

    /**
     * @var mixed Root condition
     */
    protected $conditionsRoot = false;

    /**
     * @var Backend\Widgets\Form
     */
    protected $conditionFormWidget;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $this->fillFromConfig([
            'conditionsRuleType',
        ]);

        if ($widget = $this->makeConditionFormWidget()) {
            $widget->bindToController();
        }

        $this->initRootCondition();
    }

    /**
     * {@inheritDoc}
     */
    protected function loadAssets()
    {
        $this->addJs('js/conditions.js', 'RainLab.Notify');
        $this->addJs('js/conditions.multivalue.js', 'RainLab.Notify');
        $this->addCss('css/conditions.css', 'RainLab.Notify');
    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('conditions_container');
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $this->vars['rootCondition'] = $this->getConditionsRoot();
        $this->vars['conditionFormWidget'] = $this->conditionFormWidget;
    }

    public function initRootCondition()
    {
        if ($this->getConditionsRoot()) {
            return;
        }

        $relationObject = $this->getRelationObject();

        $rootRule = $this->getRelationModel();
        $rootRule->rule_host_type = $this->conditionsRuleType;
        $rootRule->class_name = $rootRule->getRootConditionClass();
        $rootRule->save();

        $relationObject->add($rootRule, $this->sessionKey);

        $this->conditionsRoot = $rootRule;
    }

    public function isRootCondition($condition)
    {
        if ($root = $this->getConditionsRoot()) {
            return $condition->id === $root->id;
        }

        return false;
    }

    public function getConditionsRoot()
    {
        if ($this->conditionsRoot !== false) {
            return $this->conditionsRoot;
        }

        $relationObject = $this->getRelationObject();
        $rootCondition = $relationObject->withDeferred($this->sessionKey)->first();

        return $this->conditionsRoot = $rootCondition ?: null;
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        $this->model->bindEvent('model.afterSave', function() {
            $this->processSave();
        });

        return FormField::NO_SAVE_DATA;
    }

    protected function processSave()
    {
        $cache = $this->getCacheConditionDataPayload();

        foreach ($cache as $id => $data) {
            $condition = $this->findConditionObj($id);
            $attributes = $this->getCacheConditionAttributes($condition);
            $condition->fill($attributes);
            $condition->save(null, $this->sessionKey.'_'.$condition->id);
        }
    }

    //
    // AJAX
    //

    public function onLoadConditionSetup()
    {
        try {
            $condition = $this->findConditionObj();

            $this->prepareVars();

            $this->vars['condition'] = $condition;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('condition_settings_form');
    }

    public function onLoadCreateChildCondition()
    {
        try {
            $condition = $this->findConditionObj();

            /*
             * Look up parents
             */
            $parents = [$condition->id];
            $parentsArray = post('condition_parent_id', []);
            $currentId = $condition->id;

            while (array_key_exists($currentId, $parentsArray) && $parentsArray[$currentId]) {
                $parents[] = $currentId = $parentsArray[$currentId];
            }

            /*
             * Custom rules provided by model
             */
            $extraRules = [];
            if ($this->model->methodExists('getExtraConditionRules')) {
                $extraRules = $this->model->getExtraConditionRules();
            }

            /*
             * Look up conditions
             */
            $options = $condition->getChildOptions([
                'ruleType' => $this->conditionsRuleType,
                'parentIds' => $parents,
                'extraRules' => $extraRules
            ]);

            $this->prepareVars();
            $this->vars['condition'] = $condition;
            $this->vars['options'] = $options;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('create_child_form');
    }

    public function onSaveCondition()
    {
        $this->restoreCacheConditionDataPayload();

        $condition = $this->findConditionObj();

        $data = post('Condition', []);
        $condition->fill($data);
        $condition->validate();
        $condition->condition_text = $condition->getConditionObject()->getText();

        $condition->applyCustomData();

        $this->setCacheConditionData($condition);

        return $this->renderConditions($condition);
    }

    public function onCreateCondition()
    {
        if (!$className = post('condition_class')) {
            throw new ValidationException(['condition_class' => 'Please specify a condition']);
        }

        $this->restoreCacheConditionDataPayload();

        $subcondition = null;

        $parts = explode(':', $className);
        if (count($parts) > 1) {
            $subcondition = $parts[1];
            $className = $parts[0];
        }

        $parentCondition = $this->findConditionObj();

        $newCondition = $this->getRelationModel();
        $newCondition->class_name = $className;
        $newCondition->rule_host_type = $parentCondition->rule_host_type;

        if ($subcondition) {
            $newCondition->subcondition = $subcondition;
        }

        $newCondition->save();

        $parentCondition->children()->add($newCondition, post('_session_key').'_'.$parentCondition->id);

        $this->vars['newConditionId'] = $newCondition->id;

        return $this->renderConditions($parentCondition);
    }

    public function onDeleteCondition()
    {
        $parentCondition = null;

        $condition = $this->findConditionObj();

        if ($parentId = $this->getParentIdFromCondition($condition)) {
            $parentCondition = $this->findConditionObj($parentId);

            if ($parentCondition) {
                $parentCondition->children()->remove($condition, post('_session_key').'_'.$parentCondition->id);
            }
        }

        return $this->renderConditions($parentCondition);
    }

    public function onCancelConditionSettings()
    {
        $condition = $this->findConditionObj(post('new_condition_id'));

        $condition->delete();

        return $this->renderConditions();
    }

    //
    // Postback deferring
    //

    public function getCacheConditionAttributes($condition)
    {
        return array_get($this->getCacheConditionData($condition), 'attributes');
    }

    public function getCacheConditionText($condition)
    {
        return array_get($this->getCacheConditionData($condition), 'text');
    }

    public function getCacheConditionJoinText($condition)
    {
        return array_get($this->getCacheConditionData($condition), 'joinText');
    }

    public function getCacheConditionData($condition, $default = null)
    {
        $cache = post('condition_data', []);

        if (is_array($cache) && array_key_exists($condition->id, $cache)) {
            return json_decode($cache[$condition->id], true);
        }

        if ($default === false) {
            return null;
        }

        return $this->makeCacheConditionData($condition);
    }

    public function makeCacheConditionData($condition)
    {
        $data = [
            'attributes' => $condition->config_data,
            'text' => $condition->getText()
        ];

        if ($condition->isCompound()) {
            $data['joinText'] = $condition->getJoinText();
        }

        return $data;
    }

    public function setCacheConditionData($condition)
    {
        $cache = post('condition_data', []);

        $cache[$condition->id] = json_encode($this->makeCacheConditionData($condition));

        Request::merge([
            'condition_data' => $cache
        ]);
    }

    public function restoreCacheConditionDataPayload()
    {
        Request::merge([
            'condition_data' => json_decode(post('current_condition_data', []), true)
        ]);
    }

    public function getCacheConditionDataPayload()
    {
        return post('condition_data');
    }

    //
    // Helpers
    //

    public function getParentIdFromCondition($condition)
    {
        if ($parentId = post('current_parent_id')) {
            return $parentId;
        }

        $parentIds = post('condition_parent_id', []);

        if (isset($parentIds[$condition->id])) {
            return $parentIds[$condition->id];
        }
    }

    /**
     * Updates the primary rule conditions container
     * @return array
     */
    protected function renderConditions($currentCondition = null)
    {
        if ($currentCondition && $this->isRootCondition($currentCondition)) {
            $condition = $currentCondition;
        }
        else {
            $condition = $this->getConditionsRoot();
        }

        return [
            '#'.$this->getId() => $this->makePartial('conditions', ['condition' => $condition])
        ];
    }

    protected function makeConditionFormWidget()
    {
        if ($this->conditionFormWidget !== null) {
            return $this->conditionFormWidget;
        }

        if (!$model = $this->findConditionObj(null, false)) {
            return null;
        }

        $config = $model->getFieldConfig();
        $config->model = $model;
        $config->alias = $this->alias . 'Form';
        $config->arrayName = 'Condition';

        $widget = $this->makeWidget('Backend\Widgets\Form', $config);

        /*
         * Set form values based on postback or cached attributes
         */
        if (!$data = post('Condition')) {
            $data = $this->getCacheConditionAttributes($model);
        }

        $widget->setFormValues($data);

        /*
         * Allow conditions to register their own widgets
         */
        $model->onPreRender($this->controller, $this);

        return $this->conditionFormWidget = $widget;
    }

    protected function findConditionObj($conditionId = null, $throw = true)
    {
        $conditionId = $conditionId ? $conditionId : post('current_condition_id');

        $condition = null;

        if (strlen($conditionId)) {
            $condition = $this->getRelationModel()->find($conditionId);
        }

        if ($throw && !$condition) {
            throw new ApplicationException('Condition not found');
        }

        return $condition;
    }
}
