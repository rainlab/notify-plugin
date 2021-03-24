<?php

return [
    'plugin' => [
        'name' => 'Notify',
        'description' => 'Notification services',
    ],
    'notifications' => [
        'menu_label' => 'Notification rules',
        'menu_description' => 'Manage the events and actions that trigger notifications.',
        'name' => 'Name',
        'code' => 'Code',
        'notification_rule' => 'Notification rule',
        'add_notification_rule' => 'Add notification rule',
    ],
    'action' => [
        'add_notification_action' => 'Add notification action',
        'schedule' => 'Schedule',
        'schedule_notice' => 'Note that scheduling might not work for certain queue driver configurations.',
        'schedule_notice_more' => 'Learn more',
        'schedule_unsupported' => 'The configured queue driver does not support delayed execution.'
    ],
    'permissions' => [
        'manage_notifications' => 'Notifications management',
    ],
];
