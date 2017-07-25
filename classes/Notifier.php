<?php namespace RainLab\Notify\Classes;

use App;
use Queue;
use Event;
use BackendAuth;
use System\Classes\PluginManager;
use RainLab\Notify\Models\NotificationRule as NotificationRuleModel;

/**
 * Notification manager
 *
 * @package rainlab\notify
 * @author Alexey Bobkov, Samuel Georges
 */
class Notifier
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * @var array Cache of registration callbacks.
     */
    protected $callbacks = [];

    /**
     * @var bool Internal marker to see if callbacks are run.
     */
    protected $registered = false;

    /**
     * @var array List of registered global params in the system
     */
    protected $registeredGlobalParams;

    /**
     * Registers a callback function that defines context variables.
     * The callback function should register context variables by calling the manager's
     * `registerGlobalParams` method. The manager instance is passed to the callback
     * function as an argument. Usage:
     *
     *     Notifier::registerCallback(function($manager){
     *         $manager->registerGlobalParams([...]);
     *     });
     *
     * @param callable $callback A callable function.
     */
    public function registerCallback(callable $callback)
    {
        $this->callbacks[] = $callback;
    }

    /**
     * Helper to process callbacks once and once only.
     * @return void
     */
    protected function processCallbacks()
    {
        if ($this->registered) {
            return;
        }

        foreach ($this->callbacks as $callback) {
            $callback($this);
        }

        $this->registered = true;
    }

    //
    // Event binding
    //

    public static function bindEvents(array $events)
    {
        foreach ($events as $event => $class) {
            self::bindEvent($event, $class);
        }
    }

    public static function bindEvent($systemEventName, $notifyEventClass)
    {
        Event::listen($systemEventName, function() use ($notifyEventClass, $systemEventName) {
            $params = $notifyEventClass::makeParamsFromEvent(func_get_args(), $systemEventName);

            self::instance()->queueEvent($notifyEventClass, $params);
        });
    }

    public function queueEvent($eventClass, array $params)
    {
        $params += $this->getContextVars();

        // Use queue
        if (true) {
            Queue::push(new EventParams($eventClass, $params));
        }
        else {
            $this->fireEvent($eventClass, $params);
        }
    }

    public function fireEvent($eventClass, array $params)
    {
        $models = NotificationRuleModel::listRulesForEvent($eventClass);

        foreach ($models as $model) {
            $model->setParams($params);
            $model->triggerRule();
        }
    }

    public function registerGlobalParams(array $params)
    {
        if (!$this->registeredGlobalParams) {
            $this->registeredGlobalParams = [];
        }

        $this->registeredGlobalParams = $params + $this->registeredGlobalParams;
    }

    public function getContextVars()
    {
        $this->processCallbacks();

        $globals = $this->registeredGlobalParams ?: [];

        return [
            'isBackend' => App::runningInBackend() ? 1 : 0,
            'isConsole' => App::runningInConsole() ? 1 : 0,
            'appLocale' => App::getLocale(),
            'sender'    => null // unsafe:BackendAuth::getUser()
        ] + $globals;
    }
}
