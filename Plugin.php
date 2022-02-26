<?php namespace RainLab\Notify;

use Backend;
use System\Classes\PluginBase;
use System\Classes\SettingsManager;

/**
 * Plugin registration file
 */
class Plugin extends PluginBase
{
    /**
     * pluginDetails
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Notify',
            'description' => 'Notification Services',
            'author' => 'Alexey Bobkov, Samuel Georges',
            'icon' => 'icon-bullhorn'
        ];
    }

    /**
     * registerSettings
     */
    public function registerSettings()
    {
        return [
            'notifications' => [
                'label' => 'Notification Rules',
                'description' => 'Manage the events and actions that trigger notifications.',
                'category' => SettingsManager::CATEGORY_NOTIFICATIONS,
                'icon' => 'icon-bullhorn',
                'url' => Backend::url('rainlab/notify/notifications'),
                'permissions' => ['rainlab.notify.manage_notifications'],
                'order' => 600,
                'keywords' => 'notify'
            ],
        ];
    }

    /**
     * registerNotificationRules
     */
    public function registerNotificationRules()
    {
        return [
            'groups' => [],
            'events' => [],
            'actions' => [
                \RainLab\Notify\NotifyRules\SaveDatabaseAction::class,
                \RainLab\Notify\NotifyRules\SendMailTemplateAction::class,
            ],
            'conditions' => [
                \RainLab\Notify\NotifyRules\ExecutionContextCondition::class,
            ],
        ];
    }

    /**
     * registerPermissions
     */
    public function registerPermissions()
    {
        return [
            'rainlab.notify.manage_notifications' => [
                'tab' => SettingsManager::CATEGORY_NOTIFICATIONS,
                'label' => 'Notifications management'
            ],
        ];
    }
}
