<?php
namespace Pirate\Sails\SintJan\Pages\Info;

use Pirate\Wheel\Page;
use Pirate\Wheel\Template;
use Pirate\Sails\Maandplanning\Models\Event;

class VZW extends Page
{
    public function __construct()
    {
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $events = Event::getEventsForTak('vzw');
        $event_groups = [];

        foreach ($events as $event) {
            $event_groups[$event->startdate->format('Y-m')][] = $event;
        }

        ksort($event_groups);
        $event_groups = array_values($event_groups);
        return Template::render('pages/info/vzw', array(
            'events' => $event_groups,

        ));
    }
}
