<?php namespace Rainlab\Notify\Models;

use Model;
use Exception;
use SystemException;

/**
 * RuleAction Model
 */
class RuleAction extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'rainlab_notify_rule_actions';

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
    public $belongsTo = [
        'notification_rule' => [NotificationRule::class, 'key' => 'rule_host_id'],
    ];

    public function triggerAction($params)
    {
        try {
            $this->getActionObject()->triggerAction($params);
        }
        catch (Exception $ex) {
            // We could log the error here, for now we should suppress
            // any exceptions to let other actions proceed as normal
            traceLog('Error with ' . $this->getActionClass());
            traceLog($ex);
        }
    }

    /**
     * Extends this model with the action class
     * @param  string $class Class name
     * @return boolean
     */
    public function applyActionClass($class = null)
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
        if (!$actionObj = $this->getActionObject()) {
            throw new SystemException(sprintf('Unable to find action object [%s]', $this->getActionClass()));
        }

        /*
         * Spin over each field and add it to config_data
         */
        $config = $actionObj->getFieldConfig();

        /*
         * Action class has no fields
         */
        if (!isset($config->fields)) {
            return;
        }

        $staticAttributes = ['action_text'];

        $fieldAttributes = array_merge($staticAttributes, array_keys($config->fields));

        $dynamicAttributes = array_only($this->getAttributes(), $fieldAttributes);

        $this->config_data = $dynamicAttributes;

        $this->setRawAttributes(array_except($this->getAttributes(), $fieldAttributes));
    }

    public function afterFetch()
    {
        $this->applyActionClass();
        $this->loadCustomData();
    }

    public function getText()
    {
        if (strlen($this->action_text)) {
            return $this->action_text;
        }

        if ($actionObj = $this->getActionObject()) {
            return $actionObj->getText();
        }
    }

    public function getActionObject()
    {
        $this->applyActionClass();

        return $this->asExtension($this->getActionClass());
    }

    public function getActionClass()
    {
        return $this->class_name;
    }
}
