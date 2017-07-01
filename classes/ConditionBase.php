<?php namespace RainLab\Notify\Classes;

use System\Classes\PluginManager;
use October\Rain\Extension\ExtensionBase;
use RainLab\Notify\Interfaces\Condition as ConditionInterface;

/**
 * Condition Base Class
 */
class ConditionBase extends ExtensionBase implements ConditionInterface
{
    use \System\Traits\ConfigMaker;
    use \System\Traits\ViewMaker;

    const TYPE_ANY = 'any';
    const TYPE_LOCAL = 'local';

    const CAST_FLOAT = 'float';
    const CAST_STRING = 'string';
    const CAST_INTEGER = 'integer';
    const CAST_BOOLEAN = 'boolean';
    const CAST_RELATION = 'relation';

    /**
     * @var Model host object
     */
    protected $host;

    /**
     * @var mixed Extra field configuration for the condition.
     */
    protected $fieldConfig;

    /**
     * @var string The plugin class method used to look for conditions.
     */
    protected static $registrationMethod = 'registerNotificationRules';

    public function __construct($host = null)
    {
        /*
         * Paths
         */
        $this->viewPath = $this->configPath = $this->guessConfigPathFrom($this);

        /*
         * Parse the config
         */
        $this->fieldConfig = $this->makeConfig($this->defineFormFields());

        if (!$this->host = $host) {
            return;
        }

        $this->boot($host);
    }

    /**
     * Boot method called when the condition class is first loaded
     * with an existing model.
     * @return array
     */
    public function boot($host)
    {
        // Set default data
        if (!$host->exists) {
            $this->initConfigData($host);
        }

        // Apply validation rules
        $host->rules = array_merge($host->rules, $this->defineValidationRules());

        // Inject view paths to the controller through the Form widget
        $host->bindEvent('model.form.filterFields', function($form) {
            $form->getController()->addViewPath($this->getViewPaths());
        });
    }

    /**
     * Extra field configuration for the condition.
     */
    public function defineFormFields()
    {
        return 'fields.yaml';
    }

    /**
     * Initializes configuration data when the condition is first created.
     * @param  Model $host
     */
    public function initConfigData($host) {}

    /**
     * Defines validation rules for the custom fields.
     * @return array
     */
    public function defineValidationRules()
    {
        return [];
    }

    public function getText()
    {
        return 'Condition text';
    }

    /**
     * Returns a condition name for displaying in the condition selection drop-down menu
     */
    public function getName()
    {
        return 'Condition';
    }

    /**
     * Returns a condition title for displaying in the condition settings form
     */
    public function getTitle()
    {
        return 'Condition';
    }

    /**
     * This function should return one of the `ConditionBase::TYPE_*` constants 
     * depending on a place where the condition is valid
     */
    public function getConditionType()
    {
        return ConditionBase::TYPE_ANY;
    }

    public function listSubconditions()
    {
        return [];
    }

    /**
     * Returns a title to use for grouping subconditions
     * in the Create Condition drop-down menu
     */
    public function getGroupingTitle()
    {
        return null;
    }

    /**
     * Returns the field configuration used by this model.
     */
    public function getFieldConfig()
    {
        return $this->fieldConfig;
    }

    /**
     * Spins over types registered in plugin base class with `registerNotificationRules`,
     * checks if the condition type matches and adds it to an array that is returned.
     *
     * @param string $type Use `self::TYPE_*` constants
     * @return array
     */
    public static function findConditionsByType($type)
    {
        $results = [];
        $bundles = PluginManager::instance()->getRegistrationMethodValues(static::$registrationMethod);

        foreach ($bundles as $plugin => $bundle) {
            foreach ((array) array_get($bundle, 'conditions', []) as $conditionClass) {
                if (!class_exists($conditionClass)) {
                    continue;
                }

                $obj = new $conditionClass;
                if ($obj->getConditionType() != $type) {
                    continue;
                }

                $results[$conditionClass] = $obj;
            }
        }

        return $results;
    }

    public function setFormFields($fields)
    {

    }

    public function setCustomData()
    {
    }

    public function onPreRender($controller, $widget)
    {
    }

    /**
     * Checks whether the condition is TRUE for specified parameters
     * @param array $params
     * @return bool
     */
    public function isTrue(&$params)
    {
        return false;
    }

    public function getChildOptions(array $options)
    {
        return [];
    }
}
