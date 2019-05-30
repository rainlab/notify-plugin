<?php namespace RainLab\Notify\Models;

use Model;
use RainLab\Notify\Classes\CompoundCondition;
use RainLab\Notify\Interfaces\CompoundCondition as CompoundConditionInterface;
use SystemException;

/**
 * ConditionRule Model
 */
class RuleCondition extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'rainlab_notify_rule_conditions';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];
    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array The rules to be applied to the data.
     */
    public $rules = [];

    /**
     * @var array List of attribute names which are json encoded and decoded from the database.
     */
    protected $jsonable = ['config_data'];

    /**
     * @var array Relations
     */
    public $hasMany = [
        'children' => [self::class, 'key' => 'rule_parent_id', 'delete' => true],
    ];

    public $belongsTo = [
        'parent' => [self::class, 'key' => 'rule_parent_id'],
        'notification_rule'  => [NotificationRule::class, 'key'=>'rule_host_id']
    ];

    public function filterFields($fields, $context)
    {
        /*
         * Let the condition contribute
         */
        $this->getConditionObject()->setFormFields($fields);
    }

    /**
     * Extends this model with the condition class
     * @param  string $class Class name
     * @return boolean
     */
    public function applyConditionClass($class = null)
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
        return true;
    }

    public function beforeSave()
    {
        $this->setCustomData();
    }

    public function applyCustomData()
    {
        $this->setCustomData();
        $this->loadCustomData();
    }

    protected function loadCustomData()
    {
        $this->setRawAttributes((array) $this->getAttributes() + (array) $this->config_data, true);
    }

    protected function setCustomData()
    {
        /*
         * Let the condition contribute
         */
        $this->getConditionObject()->setCustomData($this);

        /*
         * Spin over each field and add it to config_data
         */
        $config = $this->getFieldConfig();

        if (!isset($config->fields)) {
            throw new SystemException('Condition class has no fields.');
        }

        $staticAttributes = ['condition_text'];

        $fieldAttributes = array_merge($staticAttributes, array_keys($config->fields));

        $dynamicAttributes = array_only($this->getAttributes(), $fieldAttributes);

        $this->config_data = $dynamicAttributes;

        $this->setRawAttributes(array_except($this->getAttributes(), $fieldAttributes));
    }

    public function afterFetch()
    {
        $this->applyConditionClass();
        $this->loadCustomData();
    }

    public function afterSave()
    {
        // Make sure that this record is removed from the DB after being removed from a rule
        $removedFromRule = $this->rule_parent_id === null && $this->getOriginal('rule_parent_id');
        if ($removedFromRule && !$this->notification_rule()->withDeferred(post('_session_key'))->exists()) {
            $this->delete();
        }
    }

    public function getText()
    {
        if (strlen($this->condition_text)) {
            return $this->condition_text;
        }

        if ($conditionObj = $this->getConditionObject()) {
            return $conditionObj->getText();
        }
    }

    public function isCompound()
    {
        return $this->getConditionObject() instanceof CompoundConditionInterface;
    }

    public function getConditionObject()
    {
        $this->applyConditionClass();

        return $this->asExtension($this->getConditionClass());
    }

    public function getConditionClass()
    {
        return $this->class_name;
    }

    public function getRootConditionClass()
    {
        return CompoundCondition::class;
    }
}
