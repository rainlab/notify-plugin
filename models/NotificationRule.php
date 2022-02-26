<?php namespace RainLab\Notify\Models;

use Lang;
use Model;
use RainLab\Notify\Classes\EventBase;
use RainLab\Notify\Classes\ConditionBase;

/**
 * Notification rule
 *
 * @package rainlab\notify
 * @author Alexey Bobkov, Samuel Georges
 */
class NotificationRule extends Model
{
    use \October\Rain\Database\Traits\Purgeable;
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    protected $table = 'rainlab_notify_notification_rules';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['config_data'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array List of attribute names which should not be saved to the database.
     */
    protected $purgeable = ['event_name'];

    /**
     * @var array The rules to be applied to the data.
     */
    public $rules = [
        'name' => 'required'
    ];

    /**
     * @var array Relations
     */
    public $hasMany = [
        'rule_conditions' => [
            RuleCondition::class,
            'key' => 'rule_host_id',
            'conditions' => 'rule_parent_id is null',
            'delete' => true
        ],
        'rule_actions' => [
            RuleAction::class,
            'key' => 'rule_host_id',
            'delete' => true
        ],
    ];

    /**
     * Kicks off this notification rule, fires the event to obtain its parameters,
     * checks the rule conditions evaluate as true, then spins over each action.
     */
    public function triggerRule()
    {
        $params = $this->getEventObject()->getParams();
        $rootCondition = $this->rule_conditions->first();

        if ($rootCondition && !$rootCondition->getConditionObject()->isTrue($params)) {
            return false;
        }

        foreach ($this->rule_actions as $action) {
            $action->setRelation('notification_rule', $this);
            $action->triggerAction($params);
        }
    }

    /**
     * Returns extra conditions provided by the event.
     * @return array
     */
    public function getExtraConditionRules()
    {
        $rules = [];

        $classes = $this->getEventObject()->defineConditions();

        foreach ($classes as $class) {
            $rules[$class] = new $class;
        }

        return $rules;
    }

    /**
     * Extends this class with the event class
     * @param  string $class Class name
     * @return boolean
     */
    public function applyEventClass($class = null)
    {
        if (!$class) {
            $class = $this->class_name;
        }

        if (!$class) {
            return false;
        }

        if (!$this->isClassExtendedWith($class)) {
            $this->extendClassWith($class);
        }

        $this->class_name = $class;
        $this->event_name = Lang::get(array_get($this->eventDetails(), 'name', 'Unknown'));
        return true;
    }

    /**
     * Returns the event class extension object.
     * @return \RainLab\Notify\Classes\NotificationEvent
     */
    public function getEventObject()
    {
        $this->applyEventClass();

        return $this->asExtension($this->getEventClass());
    }

    public function getEventClass()
    {
        return $this->class_name;
    }

    //
    // Events
    //

    public function afterFetch()
    {
        $this->applyEventClass();
    }

    public function beforeValidate()
    {
        if (!$this->applyEventClass()) {
            return;
        }
    }

    //
    // Scopes
    //

    public function scopeApplyEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeApplyClass($query, $class)
    {
        if (!is_string($class)) {
            $class = get_class($class);
        }

        return $query->where('class_name', $class);
    }

    //
    // Presets
    //

    /**
     * Returns an array of rule codes and descriptions.
     * @return array
     */
    public static function listRulesForEvent($eventClass)
    {
        $results = [];

        $dbRules = self::applyClass($eventClass)->get();
        $presets = (array) EventBase::findEventPresetsByClass($eventClass);

        foreach ($dbRules as $dbRule) {
            if ($dbRule->code) {
                unset($presets[$dbRule->code]);
            }

            if ($dbRule->is_enabled) {
                $results[] = $dbRule;
            }
        }

        foreach ($presets as $code => $preset) {
            if ($newPreset = self::createFromPreset($code, $preset)) {
                $results[] = $newPreset;
            }
        }

        return $results;
    }

    /**
     * Syncronise all file-based presets to the database.
     * @return void
     */
    public static function syncAll()
    {
        $presets = (array) EventBase::findEventPresets();
        $dbRules = self::where('code', '!=', '')->whereNotNull('code')->lists('is_custom', 'code');
        $newRules = array_diff_key($presets, $dbRules);

        /*
         * Clean up non-customized templates
         */
        foreach ($dbRules as $code => $isCustom) {
            if ($isCustom) {
                continue;
            }

            if (!array_key_exists($code, $presets) && ($record = self::whereCode($code)->first())) {
                $record->delete();
            }
        }

        /*
         * Create new rules
         */
        foreach ($newRules as $code => $preset) {
            self::createFromPreset($code, $preset);
        }
    }

    public static function createFromPreset($code, $preset)
    {
        $actions = array_get($preset, 'items');
        if (!$actions || !is_array($actions)) {
            return;
        }

        $newRule = new self;
        $newRule->code = $code;
        $newRule->is_enabled = 1;
        $newRule->is_custom = 0;
        $newRule->name = array_get($preset, 'name');
        $newRule->class_name = array_get($preset, 'event');
        $newRule->forceSave();

        // Add the actions
        foreach ($actions as $action) {
            $params = array_except($action, 'action');

            $newAction = new RuleAction;
            $newAction->class_name = array_get($action, 'action');
            $newAction->notification_rule = $newRule;
            $newAction->fill($params);
            $newAction->forceSave();
        }

        // Add the conditions
        $conditions = array_get($preset, 'conditions');
        if (!$conditions || !is_array($conditions)) {
            return $newRule;
        }

        // Create the root condition
        $rootCondition = new RuleCondition();
        $rootCondition->rule_host_type = ConditionBase::TYPE_ANY;
        $rootCondition->class_name = $rootCondition->getRootConditionClass();
        $rootCondition->notification_rule = $newRule;
        $rootCondition->save();

        // Add the sub conditions
        foreach ($conditions as $condition) {
            $params = array_except($condition, 'condition');
            $newCondition = new RuleCondition();
            $newCondition->class_name = array_get($condition, 'condition');
            $newCondition->parent = $rootCondition;
            $newCondition->fill($params);
            $newCondition->forceSave();
        }

        return $newRule;
    }
}
