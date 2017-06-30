<?php namespace RainLab\Notify\Interfaces;

/**
 * This contract represents a notification event.
 */
interface Event
{
    /**
     * Returns information about this event, including name and description.
     * @return array
     */
    public function eventDetails();

    /**
     * Generates event parameters based on arguments from the triggering system event.
     * @param array $args
     * @param string $eventName
     * @return void
     */
    public static function makeParamsFromEvent(array $args, $eventName = null);
}
