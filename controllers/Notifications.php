<?php namespace RainLab\Notify\Controllers;

use Str;
use Lang;
use File;
use Mail;
use Flash;
use Backend;
use Redirect;
use BackendMenu;
use Backend\Classes\Controller;
use RainLab\Notify\Models\NotificationRule;
use System\Classes\SettingsManager;
use RainLab\Notify\Classes\EventBase;
use ApplicationException;
use Exception;

/**
 * Notification rules controller
 *
 * @package rainlab\notify
 * @author Alexey Bobkov, Samuel Georges
 */
class Notifications extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class
    ];

    public $requiredPermissions = ['rainlab.notify.manage_notifications'];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $eventAlias;
    protected $eventClass;

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('RainLab.Notify', 'notifications');
    }

    public function index()
    {
        NotificationRule::syncAll();
        $this->asExtension('ListController')->index();
    }

    public function create($eventAlias = null)
    {
        try {
            if (!$eventAlias) {
                throw new ApplicationException('Missing a rule code');
            }

            $this->eventAlias = $eventAlias;
            $this->bodyClass = 'compact-container breadcrumb-fancy';
            $this->asExtension('FormController')->create();
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }
    }

    public function update($recordId = null, $context = null)
    {
        $this->bodyClass = 'compact-container breadcrumb-fancy';
        $this->asExtension('FormController')->update($recordId, $context);
    }

    public function formExtendModel($model)
    {
        if (!$model->exists) {
            $model->applyEventClass($this->getEventClass());
            $model->name = $model->getEventDescription();
        }

        return $model;
    }

    // public function formBeforeSave($model)
    // {
    //     $model->is_custom = 1;
    // }

    public function index_onLoadRuleGroupForm()
    {
        try {
            $groups = EventBase::findEventGroups();
            $this->vars['eventGroups'] = $groups;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('add_rule_group_form');
    }

    /**
     * This handler requires the group code passed from `onLoadRuleGroupForm`
     */
    public function index_onLoadRuleEventForm()
    {
        try {
            if (!$code = post('code')) {
                throw new ApplicationException('Missing event group code');
            }

            $events = EventBase::findEventsByGroup($code);
            $this->vars['events'] = $events;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('add_rule_event_form');
    }

    protected function getEventClass()
    {
        $alias = post('event_alias', $this->eventAlias);

        if ($this->eventClass !== null) {
            return $this->eventClass;
        }

        if (!$event = EventBase::findEventByIdentifier($alias)) {
            throw new ApplicationException('Unable to find event with alias: '. $alias);
        }

        return $this->eventClass = get_class($event);
    }
}
