<?php namespace RainLab\Notify\NotifyRules;

use Lang;
use System\Models\MailTemplate;
use RainLab\Notify\Classes\ActionBase;

class SendMailTemplateAction extends ActionBase
{
    public $recipientModes = [
        'default' => 'System default',
        'user' => 'User email address (if applicable)',
        'sender' => 'Sender user email address (if applicable)',
        'custom' => 'Specific email address',
    ];

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
     * Triggers this action.
     * @param array $params
     * @return void
     */
    public function triggerAction($params)
    {
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
        $hostObj = $this->host;

        if ($hostObj->mail_template) {
            $result = sprintf(
                'Send a message to %s using template %s',
                array_get($this->recipientModes, $hostObj->send_to_mode),
                $hostObj->mail_template
            );
        }
        else {
            $result = $this->getActionDescription();
        }

        return $result;
    }

    public function getSendToModeOptions()
    {
        return $this->recipientModes;
    }

    public function getReplyToModeOptions()
    {
        return $this->recipientModes;
    }

    public function getMailTemplateOptions()
    {
        $codes = array_keys(MailTemplate::listAllTemplates());
        $result = ['' => '- Select template -'];
        $result += array_combine($codes, $codes);
        return $result;
    }
}
