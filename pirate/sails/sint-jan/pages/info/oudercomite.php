<?php
namespace Pirate\Sails\SintJan\Pages\Info;

use Pirate\Sails\Maandplanning\Models\Event;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Oudercomite extends Page
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
        $events = Event::getEventsForTak('oudercomite');
        $event_groups = [];

        foreach ($events as $event) {
            $event_groups[$event->startdate->format('Y-m')][] = $event;
        }

        ksort($event_groups);
        $event_groups = array_values($event_groups);
        return Template::render('pages/info/oudercomite', array(
            'events' => $event_groups,

        ));
    }
}
