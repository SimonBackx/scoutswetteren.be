<?php
namespace Pirate\Sail\Maandplanning\Blocks;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Model;
use Pirate\Model\Event;

class Kalender extends Block {
    function getHead() {
        return '';
    }

    function getContent() {
        global $config;

        //Begin en einde van de huidige week berekenen
        $day = date('N')-1;
        $week_start = date('Y-m-d 00:00', strtotime('-'.$day.' days'));
        $week_end = date('Y-m-d 00:00', strtotime('+'.(7-$day).' days'));

        $week_start_datetime = new \DateTime($week_start);
        $week_end_datetime = new \DateTime($week_end);

        // Evenementen ophalen
        Model::loadModel('maandplanning', 'event');
        $events = Event::getEvents($week_start,$week_end);

        // Array die we doorgeven aan onze templates
        $data = array();

        // Alle evenementen overlopen en data toevoegen aan de array
        foreach ($events as $event) {
            $date = intval($event->startdate->format('Ymd'));
            if (!isset($data[$date])) {
                $data[$date] = array(
                    'weekday' => ucfirst(datetimeToWeekday($event->startdate)),
                    'date' => datetimeToDateString($event->startdate),
                    'date_raw' => $date,
                    'activities' => array()
                );
            }

            $time = $event->startdate->format('H:i');

            $description = $event->name;

            $end_date = intval($event->enddate->format('Ymd'));

            // Als einddatum zelfde dag is:
            if ($end_date == $date) {
                // Einduur toevoegen
                $time .= ' - '.$event->enddate->format('H:i');
            } else {
                // Einddatum toevoegen in beschrijving
                $description = 'Start "'.$event->name.'" tot '.datetimeToWeekday($event->enddate).' '.datetimeToDateString($event->enddate);

                $end_description = 'Einde "'.$event->name.'"';

                $time = 'Om ' .$time;
                $end_time = 'Tot '.$event->enddate->format('H:i');

                if (!empty($event->endlocation)) {
                    $end_time .= ', '.$event->endlocation;
                }

                // Enkel einddatum tonen als het nog in deze week is
                if ($event->enddate < $week_end_datetime) {

                    // Activiteit toevoegen die het einde van deze aanduid
                    if (!isset($data[$end_date])) {
                        $data[$end_date] = array(
                            'weekday' => ucfirst(datetimeToWeekday($event->enddate)),
                            'date' => datetimeToDateString($event->enddate),
                            'date_raw' => $end_date,
                            'activities' => array()
                        );
                    }
                    $data[$end_date]['activities'][] = array(
                        'group' => $event->group,
                        'time' => $end_time,
                        'time_raw' => $event->enddate->format('Y-m-d H:i:s'),
                        'description' => $end_description
                    );
                }

                // Enkel begindatum tonen als het in deze week is 
                // (kan enkel bij events van meerdere dagen voorkomen)
                if ($event->startdate < $week_start_datetime) {
                    continue;
                }
            }

            if (!empty($event->location)) {
                $time .= ', '.$event->location;
            }

            $data[$date]['activities'][] = array(
                'group' => $event->group,
                'time' => $time,
                'time_raw' => $event->startdate->format('Y-m-d H:i:s'),
                'description' => $description
            );
        }


        return Template::render('maandplanning/kalender', array('days' => array_values($data)));
    }

}