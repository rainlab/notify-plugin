<?php namespace RainLab\Notify\Classes;

use System\Classes\PluginManager;
use October\Rain\Extension\ExtensionBase;
use RainLab\Notify\Interfaces\Action as ActionInterface;

/**
 * Notification action base class
 *
 * @package rainlab\notify
 * @author Alexey Bobkov, Samuel Georges
 */
class ActionBase extends ExtensionBase implements ActionInterface
{
    use \System\Traits\ConfigMaker;
    use \System\Traits\ViewMaker;

    /**
     * @var Model host object
     */
    protected $host;

    /**
     * @var mixed Extra field configuration for the condition.
     */
    protected $fieldConfig;

    /**
     * Returns information about this action, including name and description.
     */
    public function actionDetails()
    {
        return [
            'name'        => 'Action',
            'description' => 'Action description',
            'icon'        => 'icon-dot-circle-o'
        ];
    }

    public function __construct($host = null)
    {
        /*
         * Paths
         */
        $this->viewPath = $this->configPath = $this->guessConfigPathFrom($this);

        /*
         * Parse the config, if available
         */
        if ($formFields = $this->defineFormFields()) {
            $this->fieldConfig = $this->makeConfig($formFields);
        }

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
    }

    public function triggerAction($params)
    {
    }

    public function getTitle()
    {
        return $this->getActionName();
    }

    public function getText()
    {
        return $this->getActionDescription();
    }

    public function getActionName()
    {
        return array_get($this->actionDetails(), 'name');
    }

    public function getActionDescription()
    {
        return array_get($this->actionDetails(), 'description');
    }

    public function getActionIcon()
    {
        return array_get($this->actionDetails(), 'icon', 'icon-dot-circle-o');
    }

    /**
     * Extra field configuration for the condition.
     */
    public function defineFormFields()
    {
        return 'fields.yaml';
    }

    /**
     * Determines if this action uses form fields.
     * @return bool
     */
    public function hasFieldConfig()
    {
        return !!$this->fieldConfig;
    }

    /**
     * Returns the field configuration used by this model.
     */
    public function getFieldConfig()
    {
        return $this->fieldConfig;
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

    /**
     * Spins over types registered in plugin base class with `registerNotificationRules`.
     * @return array
     */
    public static function findActions()
    {
        $results = [];
        $bundles = PluginManager::instance()->getRegistrationMethodValues('registerNotificationRules');

        foreach ($bundles as $plugin => $bundle) {
            foreach ((array) array_get($bundle, 'actions', []) as $conditionClass) {
                if (!class_exists($conditionClass)) {
                    continue;
                }

                $obj = new $conditionClass;
                $results[$conditionClass] = $obj;
            }
        }

        return $results;
    }
}
