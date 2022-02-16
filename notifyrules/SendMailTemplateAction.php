<?php namespace RainLab\Notify\NotifyRules;

use Mail;
use Lang;
use Config;
use System\Models\MailTemplate;
use RainLab\Notify\Classes\ActionBase;
use Backend\Models\User as AdminUserModel;
use Backend\Models\UserGroup as AdminGroupModel;
use ApplicationException;

class SendMailTemplateAction extends ActionBase
{
    public $recipientModes = [
        'system'  => /*System default*/'rainlab.notify::lang.send_mail.system',
        'user'    => /*User email address (if applicable*/'rainlab.notify::lang.send_mail.user',
        'sender'  => /*Sender user email address (if applicable*/'rainlab.notify::lang.send_mail.sender',
        'admin'   => /*Back-end administrators*/'rainlab.notify::lang.send_mail.admin',
        'custom'  => /*Specific email address*/'rainlab.notify::lang.send_mail.custom'
    ];

    /**
     * Returns information about this event, including name and description.
     */
    public function actionDetails()
    {
        return [
            'name'        => /*Compose a mail message*/trans('rainlab.notify::lang.send_mail.name'),
            'description' => /*Send a message to a recipient*/trans('rainlab.notify::lang.send_mail.description'),
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

        $recipient = $this->getRecipientAddress($params);

        $replyTo = $this->getReplyToAddress($params);

        if (!$recipient || !$template) {
            throw new ApplicationException('Missing valid recipient or mail template');
        }

        Mail::sendTo($recipient, $template, $params, function($message) use ($replyTo) {
            if ($replyTo) {
                $message->replyTo($replyTo);
            }
        });
    }

    /**
     * Field configuration for the action.
     */
    public function defineFormFields()
    {
        return 'fields.yaml';
    }

    /**
     * Defines validation rules for the custom fields.
     * @return array
     */
    public function defineValidationRules()
    {
        return [
            'mail_template' => 'required',
            'send_to_mode' => 'required',
        ];
    }

    public function getTitle()
    {
        if ($this->isAdminMode()) {
            return 'Compose mail to administrators';
        }

        return parent::getTitle();
    }

    public function getActionIcon()
    {
        if ($this->isAdminMode()) {
            return 'icon-envelope-square';
        }

        return parent::getActionIcon();
    }

    public function getText()
    {
        $hostObj = $this->host;

        $recipient = array_get($this->recipientModes, $hostObj->send_to_mode);

        if ($this->isAdminMode()) {
            if ($groupId = $this->host->send_to_admin) {
                if ($group = AdminGroupModel::find($groupId)) {
                    $adminText = $group->name;
                }
                else {
                    $adminText = '?';
                }

                $adminText .= /* admin group*/trans('rainlab.notify::lang.send_mail.admin_group');
            }
            else {
                $adminText = /*all admins*/trans('rainlab.notify::lang.send_mail.admin_all');
            }
            return sprintf(
                /*Send a message to %s using template %s*/trans('rainlab.notify::lang.send_mail.send_message'),
                $adminText,
                $hostObj->mail_template
            );
        }

        if ($hostObj->mail_template) {

            return sprintf(
                /*Send a message to %s using template %s*/trans('rainlab.notify::lang.send_mail.send_message'),
                trans(mb_strtolower($recipient)),
                trans($hostObj->mail_template)
            );
        }

        return parent::getText();
    }

    public function getSendToAdminOptions()
    {
        $options = ['' => /*- All administrators -*/trans('rainlab.notify::lang.send_mail.all_admin_group')];

        $groups = AdminGroupModel::lists('name', 'id');

        return $options + $groups;
    }

    public function getSendToModeOptions()
    {
        $modes = $this->recipientModes;

        unset($modes['system']);

        return $modes;
    }

    public function getReplyToModeOptions()
    {
        $modes = $this->recipientModes;

        unset($modes['admin']);

        return $modes;
    }

    public function getMailTemplateOptions()
    {
        $codes = array_keys(MailTemplate::listAllTemplates());

        $result = array_combine($codes, $codes);

        // Wrap to prevent collisions with language keys
        array_walk($result, function(&$value, $key) {
            $value = "[$value]";
        });

        return $result;
    }

    protected function getReplyToAddress($params)
    {
        $mode = $this->host->reply_to_mode;

        if ($mode == 'custom') {
            return $this->host->reply_to_custom;
        }

        if ($mode == 'user' || $mode == 'sender') {
            $obj = array_get($params, $mode);
            return $obj->email;
        }
    }

    protected function getRecipientAddress($params)
    {
        $mode = $this->host->send_to_mode;

        if ($mode == 'custom') {
            return $this->host->send_to_custom;
        }

        if ($mode == 'system') {
            $name = Config::get('mail.from.name', 'Your Site');
            $address = Config::get('mail.from.address', 'admin@domain.tld');
            return [$address => $name];
        }

        if ($mode == 'admin') {
            if ($groupId = $this->host->send_to_admin) {
                if (!$group = AdminGroupModel::find($groupId)) {
                    throw new ApplicationException('Unable to find admin group with ID: '.$groupId);
                }

                return $group->users->lists('full_name', 'email');
            }
            else {
                return AdminUserModel::all()->lists('full_name', 'email');
            }
        }

        if ($mode == 'user' || $mode == 'sender') {
            return array_get($params, $mode);
        }
    }

    protected function isAdminMode()
    {
        return $this->host->send_to_mode == 'admin';
    }
}
