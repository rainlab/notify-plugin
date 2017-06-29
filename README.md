# Notification engine

**Plugin is currently in Beta status. Proceed with caution.**

Adds support for sending notifications across a variety of different channels, including mail, SMS and Slack.

Notifications are managed in the back-end area by navigating to *Settings > Notification rules*.

## Notification workflow

When a notification fires, it uses the following workflow:

1. Plugin registers associated actions, conditions and events using `registerNotificationRules`
1. A notification class is bound to a system event using `Notifier::bindEvent`
1. A traditional event is fired `Event::fire`
1. The parameters of the event are captured, along with any context parameters
1. A command is pushed on the queue to process the notification `Queue::push`
1. The command finds all notification rules using the notification class and triggers them
1. The notification conditions are checked and only proceed if met
1. The notification actions are triggered

Here is an example of a plugin registering notification rules. The `groups` definition will create containers that are used to better organise events.

    public function registerNotificationRules()
    {
        return [
            'events' => [
                \RainLab\User\NotifyRules\UserActivatedEvent::class,
            ],
            'actions' => [
                \RainLab\User\NotifyRules\SaveToDatabaseAction::class,
            ],
            'conditions' => [
                \RainLab\User\NotifyRules\UserAttributeCondition::class
            ],
            'groups' => [
                'user' => [
                    'label' => 'User',
                    'icon' => 'icon-user'
                ],
            ],
        ];
    }

Here is an example of triggering a notification. The system event `rainlab.user.activate` is bound to the `UserActivatedEvent` class.

    // Bind to a system event
    \RainLab\Notify\Classes\Notifier::bindEvent(
        'rainlab.user.activate',
        \RainLab\User\NotifyRules\UserActivatedEvent::class
    );

    // Fire the system event
    Event::fire('rainlab.user.activate', [$this]);

Here is an example of registering context parameters, which are available globally to all notifications.

    \RainLab\Notify\Classes\Notifier::instance()->registerCallback(function($manager) {
        $manager->registerContextVars([
            'user' => Auth::getUser()
        ]);
    });

## Creating Event classes

An event class is responsible for preparing the properties that will be passed to the conditions and actions. The static method `makePropertiesFromEvent` will take the arguments provided by the system event and convert them in to parameters.

    class UserActivatedEvent extends \RainLab\Notify\Classes\EventBase
    {
        /**
         * Returns information about this event, including name and description.
         */
        public function eventDetails()
        {
            return [
                'name'        => 'Activated',
                'description' => 'A user is activated',
                'group'       => 'user'
            ];
        }

        /**
         * Defines the properties used by this class.
         */
        public function defineProperties()
        {
            return [
                'user' => [
                    'title' => 'User',
                    'description' => 'The activated user',
                ],
            ];
        }

        public static function makePropertiesFromEvent(array $args, $eventName = null)
        {
            return [
                'user' => array_get($args, 0)
            ];
        }
    }

## Creating Action classes

Action classes define the final step in a notification and subsequently perform the notification itself. Some examples might be sending and email or writing to the database.

    class SendMailTemplateAction extends \RainLab\Notify\Classes\ActionBase
    {
        /**
         * Returns information about this event, including name and description.
         */
        public function actionDetails()
        {
            return [
                'name'        => 'Compose a mail message',
                'description' => 'Send a message to a recipient',
                'icon'        => 'icon-envelope'
            ];
        }

        /**
         * Field configuration for the action.
         */
        public function defineFormFields()
        {
            return 'fields.yaml';
        }

        public function getText()
        {
            $template = $this->host->template_name;

            return 'Send a message using '.$template;
        }

        /**
         * Triggers this action.
         * @param array $params
         * @return void
         */
        public function triggerAction($params)
        {
            // Sends the mail
        }
    }

A form fields definition file is used to provide form fields when the action is established. These values are accessed from condition using the host model via the `$this->host` property.

    # ===================================
    #  Field Definitions
    # ===================================

    fields:

        template_name:
            label: Template name
            type: text

An action may choose to provide no form fields by simply returning false from the `defineFormFields` method.

    public function defineFormFields()
    {
        return false;
    }

## Creating Condition classes

A condition class should specify how it should appear in the user interface, providing a name, title and summary text. It also must declare an `isTrue` method for evaluating whether the condition is true or not.

    class MyCondition extends \RainLab\Notify\Classes\ConditionBase
    {
        /**
         * Field configuration for the condition.
         */
        public function defineFormFields()
        {
            return 'fields.yaml';
        }

        public function getName()
        {
            return 'My condition is checked';
        }

        public function getTitle()
        {
            return 'My condition';
        }

        public function getText()
        {
            $value = $this->host->mycondition;

            return 'My condition <span class="operator">is</span> '.$value;
        }

        /**
         * Checks whether the condition is TRUE for specified parameters
         * @param array $params
         * @return bool
         */
        public function isTrue(&$params)
        {
            return true;
        }
    }

A form fields definition file is used to provide form fields when the condition is established. These values are accessed from condition using the host model via the `$this->host` property.

    # ===================================
    #  Field Definitions
    # ===================================

    fields:

        mycondition:
            label: My condition
            type: dropdown
            options:
                true: True
                false: False
