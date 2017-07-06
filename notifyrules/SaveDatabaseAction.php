<?php namespace RainLab\Notify\NotifyRules;

use Ramsey\Uuid\Uuid;
use RainLab\Notify\Classes\ActionBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class SaveDatabaseAction extends ActionBase
{
    protected $tableDefinitions;

    /**
     * Returns information about this event, including name and description.
     */
    public function actionDetails()
    {
        return [
            'name'        => 'Store in database',
            'description' => 'Log event data in the notifications activity log',
            'icon'        => 'icon-database'
        ];
    }

    public function defineFormFields()
    {
        return 'fields.yaml';
    }

    public function getText()
    {
        if ($this->host->related_object) {
            $label = array_get($this->getRelatedObjectOptions(), $this->host->related_object);

            return 'Log event in the '.$label.' log';
        }

        return parent::getText();
    }

    /**
     * Triggers this action.
     * @param array $params
     * @return void
     */
    public function triggerAction($params)
    {
        if (
            (!$definition = array_get($this->tableDefinitions, $this->host->related_object)) ||
            (!$param = array_get($definition, 'param')) ||
            (!$value = array_get($params, $param))
        ) {
            throw new ApplicationException('Error evaluating the save database action: the related object is not found in the action parameters.');
        }

        if (!$value instanceof EloquentModel) {
            // @todo Perhaps value is an ID or a model array,
            // look up model $definition[class] from ID ...
        }

        $rule = $this->host->notification_rule;
        $relation = array_get($definition, 'relation');

        $value->$relation()->create([
            'id' => Uuid::uuid4()->toString(),
            'event_type' => $rule->getEventClass(),
            'icon' => $this->host->icon,
            'type' => $this->host->type,
            'body' => $this->host->body,
            'data' => $this->getData($params),
            'read_at' => null,
        ]);
    }

    /**
     * Get the data for the notification.
     *
     * @param  array  $notifiable
     * @return array
     */
    protected function getData($params)
    {
        // This should check for params that cannot be jsonable.
        return $params;
    }

    public function getRelatedObjectOptions()
    {
        $result = [];

        foreach ($this->tableDefinitions as $key => $definition) {
            $result[$key] = array_get($definition, 'label');
        }

        return $result;
    }

    public function getTableDefinitions()
    {
        return $this->tableDefinitions;
    }

    public function addTableDefinition($options)
    {
        if (!$className = array_get($options, 'class')) {
            throw new ApplicationException('Missing class name from table definition.');
        }

        $options = array_merge([
            'label' => 'Undefined table',
            'class' => null,
            'param' => null,
            'relation' => 'notifications',
        ], $options);

        $keyName = $className . '@' . array_get($options, 'relation');

        $this->tableDefinitions[$keyName] = $options;
    }
}
