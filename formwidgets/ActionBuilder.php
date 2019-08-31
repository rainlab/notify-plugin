<?php namespace RainLab\Notify\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use RainLab\Notify\Classes\ActionBase;
use ApplicationException;
use ValidationException;
use Exception;
use Request;

/**
 * Action builder
 */
class ActionBuilder extends FormWidgetBase
{
    use \Backend\Traits\FormModelWidget;

    //
    // Configurable properties
    //


    //
    // Object properties
    //

    /**
     * @var mixed Actions cache
     */
    protected $actionsCache = false;

    /**
     * @var Backend\Widgets\Form
     */
    protected $actionFormWidget;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        // $this->fillFromConfig([
        // ]);

        if ($widget = $this->makeActionFormWidget()) {
            $widget->bindToController();
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function loadAssets()
    {
        $this->addJs('js/actions.js', 'RainLab.Notify');
        $this->addCss('css/actions.css', 'RainLab.Notify');
    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('actions_container');
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $this->vars['formModel'] = $this->model;
        $this->vars['actions'] = $this->getActions();
        $this->vars['actionFormWidget'] = $this->actionFormWidget;
        $this->vars['availableTags'] = $this->getAvailableTags();
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        $this->model->bindEvent('model.afterSave', function() {
            $this->processSave();
        });

        return FormField::NO_SAVE_DATA;
    }

    protected function processSave()
    {
        $cache = $this->getCacheActionDataPayload();

        foreach ($cache as $id => $data) {
            $action = $this->findActionObj($id);

            if ($attributes = $this->getCacheActionAttributes($action)) {
                $action->fill($attributes);
            }

            $action->save(null, $this->sessionKey);
        }
    }

    //
    // AJAX
    //

    public function onLoadCreateActionForm()
    {
        try {
            $actions = ActionBase::findActions();
            $this->vars['actions'] = $actions;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('create_action_form');
    }

    public function onSaveAction()
    {
        $this->restoreCacheActionDataPayload();

        $action = $this->findActionObj();

        $data = post('Action', []);
        $action->fill($data);
        $action->validate();
        $action->action_text = $action->getActionObject()->getText();

        $action->applyCustomData();

        $this->setCacheActionData($action);

        return $this->renderActions($action);
    }

    public function onLoadActionSetup()
    {
        try {
            $action = $this->findActionObj();

            $data = $this->getCacheActionAttributes($action);

            $this->actionFormWidget->setFormValues($data);

            $this->prepareVars();
            $this->vars['action'] = $action;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('action_settings_form');
    }

    public function onCreateAction()
    {
        if (!$className = post('action_class')) {
            throw new ApplicationException('Please specify an action');
        }

        $this->restoreCacheActionDataPayload();

        $newAction = $this->getRelationModel();
        $newAction->class_name = $className;
        $newAction->save();

        $this->model->rule_actions()->add($newAction, post('_session_key'));

        $this->vars['newActionId'] = $newAction->id;

        return $this->renderActions();
    }

    public function onDeleteAction()
    {
        $action = $this->findActionObj();

        $this->model->rule_actions()->remove($action, post('_session_key'));

        return $this->renderActions();
    }

    public function onCancelActionSettings()
    {
        $action = $this->findActionObj(post('new_action_id'));

        $action->delete();

        return $this->renderActions();
    }

    //
    // Postback deferring
    //

    public function getCacheActionAttributes($action)
    {
        return array_get($this->getCacheActionData($action), 'attributes');
    }

    public function getCacheActionTitle($action)
    {
        return array_get($this->getCacheActionData($action), 'title');
    }

    public function getCacheActionText($action)
    {
        return array_get($this->getCacheActionData($action), 'text');
    }

    public function getCacheActionData($action, $default = null)
    {
        $cache = post('action_data', []);

        if (is_array($cache) && array_key_exists($action->id, $cache)) {
            return json_decode($cache[$action->id], true);
        }

        if ($default === false) {
            return null;
        }

        return $this->makeCacheActionData($action);
    }

    public function makeCacheActionData($action)
    {
        $data = [
            'attributes' => $action->config_data,
            'title' => $action->getTitle(),
            'text' => $action->getText(),
        ];

        return $data;
    }

    public function setCacheActionData($action)
    {
        $cache = post('action_data', []);

        $cache[$action->id] = json_encode($this->makeCacheActionData($action));

        Request::merge([
            'action_data' => $cache
        ]);
    }

    public function restoreCacheActionDataPayload()
    {
        Request::merge([
            'action_data' => json_decode(post('current_action_data'), true)
        ]);
    }

    public function getCacheActionDataPayload()
    {
        return post('action_data', []);
    }

    //
    // Helpers
    //

    protected function getAvailableTags()
    {
        $tags = [];

        if ($this->model->methodExists('defineParams')) {
            $params = $this->model->defineParams();

            foreach ($params as $param => $definition) {
                $tags[$param] = array_get($definition, 'label');
            }
        }

        return $tags;
    }

    /**
     * Updates the primary rule actions container
     * @return array
     */
    protected function renderActions()
    {
        $this->prepareVars();

        return [
            '#'.$this->getId() => $this->makePartial('actions')
        ];
    }

    protected function makeActionFormWidget()
    {
        if ($this->actionFormWidget !== null) {
            return $this->actionFormWidget;
        }

        if (!$model = $this->findActionObj(null, false)) {
            return null;
        }

        if (!$model->hasFieldConfig()) {
            return null;
        }

        $config = $model->getFieldConfig();
        $config->model = $model;
        $config->alias = $this->alias . 'Form';
        $config->arrayName = 'Action';

        $widget = $this->makeWidget('Backend\Widgets\Form', $config);

        return $this->actionFormWidget = $widget;
    }

    protected function getActions()
    {
        if ($this->actionsCache !== false) {
            return $this->actionsCache;
        }

        $relationObject = $this->getRelationObject();
        $actions = $relationObject->withDeferred($this->sessionKey)->get();

        return $this->actionsCache = $actions ?: null;
    }

    protected function findActionObj($actionId = null, $throw = true)
    {
        $actionId = $actionId ? $actionId : post('current_action_id');

        $action = null;

        if (strlen($actionId)) {
            $action = $this->getRelationModel()->find($actionId);
        }

        if ($throw && !$action) {
            throw new ApplicationException('Action not found');
        }

        return $action;
    }
}
