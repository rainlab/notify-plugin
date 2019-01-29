<?php namespace RainLab\Notify;

use Backend;
use System\Classes\PluginBase;
use System\Classes\SettingsManager;

/**
 * The plugin.php file (called the plugin initialization script) defines the plugin information class.
 */
class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'rainlab.notify::lang.plugin.name',
            'description' => 'rainlab.notify::lang.plugin.description',
            'author'      => 'Alexey Bobkov, Samuel Georges',
            'icon'        => 'icon-bullhorn'
        ];
    }

    public function registerSettings()
    {
        return [
            'notifications' => [
                'label'       => 'rainlab.notify::lang.notifications.menu_label',
                'description' => 'rainlab.notify::lang.notifications.menu_description',
                'category'    => SettingsManager::CATEGORY_NOTIFICATIONS,
                'icon'        => 'icon-bullhorn',
                'url'         => Backend::url('rainlab/notify/notifications'),
                'permissions' => ['rainlab.notify.manage_notifications'],
                'order'       => 600
            ],
        ];
    }

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

    public function registerPermissions()
    {
        return [
            'rainlab.notify.manage_notifications' => [
                'tab' => SettingsManager::CATEGORY_NOTIFICATIONS,
                'label' => 'rainlab.notify::lang.permissions.manage_notifications'
            ],
        ];
    }
}
