<?php
namespace Pirate\Sails\Maandplanning\Api;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;
use Pirate\Sails\Maandplanning\Models\Event;

// start = inclusive Y-m-d
// end = exclusive Y-m-d
class Search extends Page {
    private $needle;

    function __construct($needle) {
        $this->needle = $needle;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $events = Event::searchEvents($this->needle);

        $data = array('results' => array());

        foreach ($events as $event) {
            $multiple_days = ($event->startdate->format('Ymd') != $event->enddate->format('Ymd'));

            $time = $event->startdate->format('H:i');
            $time_str = ucfirst(datetimeToWeekday($event->startdate)).' '.datetimeToDateString($event->startdate);
            if ($multiple_days) {
                $time_str .= ', '.$time.' tot '. datetimeToDateString($event->enddate).', '.$event->enddate->format('H:i');
            } else {
                $time_str .= ' van '.$time.' tot '.$event->enddate->format('H:i');;
            }

            if (!empty($event->location)) {
                $time_str .= ', '.$event->location;
            }

            $data['results'][] = array(
                'name' => $event->name,
                'time' => $time_str,
                'group' => $event->group,
                'in_past' => $event->in_past
            );
        }

        return Template::render('pages/maandplanning/search', $data );
    }
}