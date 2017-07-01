<?php namespace RainLab\Notify\Models;

use Model;
use ApplicationException;

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

        if (!$rootCondition) {
            throw new ApplicationException('Notification rule is missing a root condition!');
        }

        if (!$rootCondition->getConditionObject()->isTrue($params)) {
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
        $this->event_name = array_get($this->eventDetails(), 'name', 'Unknown');
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
}
