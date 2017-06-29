<?php namespace RainLab\Notify\NotifyRules;

use RainLab\Notify\Classes\ActionBase;

class SaveDatabaseAction extends ActionBase
{
    /**
     * Returns information about this event, including name and description.
     */
    public function actionDetails()
    {
        return [
            'name'        => 'Save event to database',
            'description' => 'Store information this event in the database',
            'icon'        => 'icon-database'
        ];
    }

    public function defineFormFields()
    {
        return false;
    }
}
