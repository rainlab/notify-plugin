<?php namespace RainLab\Notify\Classes;

use Str;
use Yaml;
use File;
use Lang;
use System\Classes\PluginManager;
use October\Rain\Extension\ExtensionBase;
use RainLab\Notify\Interfaces\Event as EventInterface;

/**
 * Notification event base class
 *
 * @package rainlab\notify
 * @author Alexey Bobkov, Samuel Georges
 */
class EventBase extends ExtensionBase implements EventInterface
{
    /**
     * @var Model host object
     */
    protected $host;

    /**
     * @var array Contains the event parameter values.
     */
    protected $params = [];

    /**
     * @var array Local conditions supported by this event.
     */
    public $conditions = [];

    /**
     * Returns information about this event, including name and description.
     */
    public function eventDetails()
    {
        return [
            'name'        => 'Event',
            'description' => 'Event description',
            'group'       => 'groupcode'
        ];
    }

    public function __construct($host = null)
    {
        $this->host = $host;
    }

    /**
     * Defines the parameters used by this class.
     * This method should be used as an override in the extended class.
     */
    public function defineParams()
    {
        return [];
    }

    /**
     * Local conditions supported by this event.
     */
    public function defineConditions()
    {
        return $this->conditions;
    }

    /**
     * Sets multiple params.
     * @param array $params
     * @return void
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * Returns all params.
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Generates event parameters based on arguments from the triggering system event.
     * @param array $args
     * @param string $eventName
     * @return void
     */
    public static function makeParamsFromEvent(array $args, $eventName = null)
    {
    }

    public function getEventName()
    {
        return Lang::get(array_get($this->eventDetails(), 'name'));
    }

    public function getEventDescription()
    {
        return Lang::get(array_get($this->eventDetails(), 'description'));
    }

    public function getEventGroup()
    {
        return array_get($this->eventDetails(), 'group');
    }

    /**
     * Resolves an event or action identifier from a class name or object.
     * @param mixed Class name or object
     * @return string Identifier in format of vendor-plugin-class
     */
    public function getEventIdentifier()
    {
        $namespace = Str::normalizeClassName(get_called_class());
        if (strpos($namespace, '\\') === null) {
            return $namespace;
        }

        $parts = explode('\\', $namespace);
        $class = array_pop($parts);
        $slice = array_slice($parts, 1, 2);
        $code = strtolower(implode('-', $slice) . '-' . $class);

        return $code;
    }

    /**
     * Spins over types registered in plugin base class with `registerNotificationRules`.
     * @return array
     */
    public static function findEvents()
    {
        $results = [];
        $bundles = PluginManager::instance()->getRegistrationMethodValues('registerNotificationRules');

        foreach ($bundles as $plugin => $bundle) {
            foreach ((array) array_get($bundle, 'events', []) as $conditionClass) {
                if (!class_exists($conditionClass)) {
                    continue;
                }

                $obj = new $conditionClass;
                $results[$conditionClass] = $obj;
            }
        }

        return $results;
    }

    public static function findEventGroups()
    {
        $results = [];
        $bundles = PluginManager::instance()->getRegistrationMethodValues('registerNotificationRules');

        foreach ($bundles as $plugin => $bundle) {
            if ($groups = array_get($bundle, 'groups')) {
                $results += $groups;
            }
        }

        return $results;
    }

    public static function findEventsByGroup($group)
    {
        $results = [];

        foreach (self::findEvents() as $conditionClass => $obj) {
            if ($obj->getEventGroup() != $group) {
                continue;
            }

            $results[$conditionClass] = $obj;
        }

        return $results;
    }

    public static function findEventByIdentifier($identifier)
    {
        foreach (self::findEvents() as $class => $obj) {
            if ($obj->getEventIdentifier() == $identifier) {
                return $obj;
            }
        }
    }

    /**
     * Spins over preset registered in plugin base class with `registerNotificationRules`.
     * @return array
     */
    public static function findEventPresets()
    {
        $results = [];
        $bundles = PluginManager::instance()->getRegistrationMethodValues('registerNotificationRules');

        foreach ($bundles as $plugin => $bundle) {
            if (!$presets = array_get($bundle, 'presets')) {
                continue;
            }

            if (!is_array($presets)) {
                $presets = Yaml::parse(File::get(File::symbolizePath($presets)));
            }

            if ($presets && is_array($presets)) {
                $results += $presets;
            }
        }

        return $results;
    }

    public static function findEventPresetsByClass($className)
    {
        $results = [];

        foreach (self::findEventPresets() as $code => $definition) {
            if (!$eventClass = array_get($definition, 'event')) {
                continue;
            }

            if ($eventClass != $className) {
                continue;
            }

            $results[$code] = $definition;
        }

        return $results;
    }
}
