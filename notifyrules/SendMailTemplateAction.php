<?php namespace RainLab\Notify\NotifyRules;

use Mail;
use Lang;
use Config;
use System\Models\MailTemplate;
use RainLab\Notify\Classes\ActionBase;

class SendMailTemplateAction extends ActionBase
{
    public $recipientModes = [
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
        $template = $this->host->mail_template;

        $recipient = $this->getRecipientAddress($this->host->send_to_mode, $params);

        if (!$recipient || !$template) {
            return;
        }

        Mail::sendTo($recipient, $template, $params);
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
        return ['default' => 'System default'] + $this->recipientModes;
    }

    public function getMailTemplateOptions()
    {
        $codes = array_keys(MailTemplate::listAllTemplates());
        $result = ['' => '- Select template -'];
        $result += array_combine($codes, $codes);
        return $result;
    }

    protected function getRecipientAddress($mode, $params)
    {
        if ($mode == 'custom') {
            return ['email@todo.tld' => 'TODO'];
        }

        if ($mode == 'default') {
            $name = Config::get('mail.from.name', 'Your Site');
            $address = Config::get('mail.from.address', 'admin@domain.tld');
            return [$address => $name];
        }

        return array_get($params, $mode);
    }
}
