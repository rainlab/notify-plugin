<?php namespace RainLab\Notify\Classes;

use Rainlab\Notify\Models\RuleAction;

class ScheduledAction
{
    use \Illuminate\Queue\InteractsWithQueue;
    use \Illuminate\Queue\SerializesAndRestoresModelIdentifiers;

    protected $action;
    protected $params;

    /**
     * Create a new job instance.
     *
     * @param  array  $params
     * @return void
     */
    public function __construct($action, array $params)
    {
        $this->action = $action->id;
        $this->params = $this->serializeParams($params);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->delete();

        if (! $actionClass = RuleAction::find($this->action)) {
            return traceLog('Error: Could not restore action #' . $this->action);
        }

        $params = $this->unserializeParams();
        $actionClass->triggerAction($params, false);
    }

    protected function serializeParams($params)
    {
        $result = [];

        foreach ($params as $param => $value) {
            $result[$param] = $this->getSerializedPropertyValue($value);
        }

        return $result;
    }

    protected function unserializeParams()
    {
        $result = [];

        foreach ($this->params as $param => $value) {
            $result[$param] = $this->getRestoredPropertyValue($value);
        }

        return $result;
    }
}
