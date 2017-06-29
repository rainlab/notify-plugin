<?php namespace RainLab\Notify\Classes;

use Str;
use Event;
use System\Classes\PluginManager;
use October\Rain\Extension\ExtensionBase;
use RainLab\Notify\Classes\Notifier;
use RainLab\Notify\Interfaces\Event as EventInterface;

/**
 * Notification event base class
 *
 * @package rainlab\notify
 * @author Alexey Bobkov, Samuel Georges
 */
class EventBase extends ExtensionBase implements EventInterface
{
    use \System\Traits\PropertyContainer;

    /**
     * @var Model host object
     */
    protected $host;

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
     * Generates event properties based on arguments from the triggering system event.
     * @param array $args
     * @param string $eventName
     * @return void
     */
    public static function makePropertiesFromEvent(array $args, $eventName = null)
    {
    }

    public function getEventName()
    {
        return array_get($this->eventDetails(), 'name');
    }

    public function getEventDescription()
    {
        return array_get($this->eventDetails(), 'description');
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
}
