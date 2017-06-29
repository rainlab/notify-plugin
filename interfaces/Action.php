<?php namespace RainLab\Notify\Interfaces;

/**
 * This contract represents a notification action.
 */
interface Action
{
    /**
     * Returns a action text summary when displaying to the user.
     * @return string
     */
    public function getText();

    /**
     * Returns a action title for displaying in the action settings form.
     * @return string
     */
    public function getTitle();

    /**
     * Returns information about this action, including name and description.
     */
    public function actionDetails();

    /**
     * Triggers this action.
     * @param array $params
     * @return void
     */
    public function triggerAction($params);
}
