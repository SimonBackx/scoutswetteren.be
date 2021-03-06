<?php
namespace Pirate\Sails\Maandplanning\Blocks;

use Pirate\Wheel\Block;
use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Environment\Classes\Localization;
use Pirate\Sails\Maandplanning\Models\Event;
use Pirate\Wheel\Template;

class Kalender extends Block
{

    // start = inclusive Y-m-d
    // end = exclusive Y-m-d
    // empty array on fail
    public function getRawEvents($start, $end)
    {
        //Begin en einde van de huidige week berekenen
        $week_start = $start . ' 00:00';
        $week_end = $end . ' 00:00';

        try {
            $week_start_datetime = new \DateTime($week_start);
            $week_end_datetime = new \DateTime($week_end);
        } catch (Exception $e) {
            return array();
        }

        // Evenementen ophalen
        $events = Event::getEvents($week_start, $week_end);

        // Array die we doorgeven aan onze templates
        $data = array();

        // Alle evenementen overlopen en data toevoegen aan de array
        foreach ($events as $event) {
            $date = intval($event->startdate->format('Ymd'));

            $time = $event->startdate->format('H:i');

            $description = $event->name;

            $end_date = intval($event->enddate->format('Ymd'));

            $location_js = array(
                '@type' => 'Place',
                "name" => Environment::getSetting('name'),
                "url" => "https://" . (Environment::getSetting('force_www') ? 'www.' : '') . Environment::getSetting('domain'),
                "address" => array(
                    "@type" => "PostalAddress",
                    "addressCountry" => Environment::getSetting('address.country'),
                    "addressLocality" => Environment::getSetting('address.city'),
                    "addressRegion" => Environment::getSetting('address.region'),
                    "postalCode" => Environment::getSetting('address.postalcode'),
                    "streetAddress" => Environment::getSetting('address.street') . ' ' . Environment::getSetting('address.number'),
                ),
            );

            if (!empty($event->location)) {
                $location_js = array(
                    '@type' => 'Place',
                    "name" => $event->location,
                );
            }

            $location_js = json_encode($location_js);

            // Als einddatum zelfde dag is:
            if ($end_date == $date) {
                // Einduur toevoegen
                $time .= ' - ' . $event->enddate->format('H:i');
            } else {
                // Einddatum toevoegen in beschrijving
                $description = 'Start "' . $event->name . '" tot ' . datetimeToWeekday($event->enddate) . ' ' . datetimeToDateString($event->enddate);

                $end_description = 'Einde "' . $event->name . '"';

                $time = 'Om ' . $time;
                $end_time = 'Tot ' . $event->enddate->format('H:i');

                if (!empty($event->endlocation)) {
                    $end_time .= ', ' . $event->endlocation;
                }

                // Enkel einddatum tonen als het nog in deze week is
                if ($event->enddate < $week_end_datetime) {

                    // Activiteit toevoegen die het einde van deze aanduid
                    if (!isset($data[$end_date])) {
                        $data[$end_date] = array(
                            'weekday' => ucfirst(datetimeToWeekday($event->enddate)),
                            'date' => datetimeToDateString($event->enddate),
                            'date_raw' => $end_date,
                            'activities' => array(),
                        );
                    }
                    $data[$end_date]['activities'][] = array(
                        'group' => $event->group,
                        'time' => $end_time,
                        'time_raw' => $event->enddate->format('Y-m-d H:i:s'),
                        'description' => $end_description,
                        'name_js' => json_encode($event->name),
                        'location_js' => $event->location,
                        'time_start_js' => $event->startdate->format('c'),
                        'time_end_js' => $event->enddate->format('c'),
                        'location_js' => $location_js,
                        'event' => $event,
                    );
                }

                // Enkel begindatum tonen als het in deze week is
                // (kan enkel bij events van meerdere dagen voorkomen)
                if ($event->startdate < $week_start_datetime) {
                    continue;
                }
            }

            if (!empty($event->location)) {
                $time .= ', ' . $event->location;
            }

            if (!isset($data[$date])) {
                $data[$date] = array(
                    'weekday' => ucfirst(datetimeToWeekday($event->startdate)),
                    'date' => datetimeToDateString($event->startdate),
                    'date_raw' => $date,
                    'activities' => array(),
                );
            }

            $data[$date]['activities'][] = array(
                'group' => $event->group,
                'time' => $time,
                'time_raw' => $event->startdate->format('Y-m-d H:i:s'),
                'description' => $description,
                'name_js' => json_encode($event->name),
                'location_js' => $event->location,
                'time_start_js' => $event->startdate->format('c'),
                'time_end_js' => $event->enddate->format('c'),
                'location_js' => $location_js,
                'event' => $event,
            );
        }

        // sorteren zodat alles in de volgorde van datum staat
        ksort($data);

        return array_values($data);
    }

    // Geeft enkel de activities (ideaal voor ajax request)
    // start = inclusive Y-m-d
    // end = exclusive Y-m-d
    public function getEvents($start, $end)
    {
        return Template::render('pages/maandplanning/events', array('days' => $this->getRawEvents($start, $end)));
    }

    // Geeft volledige block
    public function getContent()
    {
        $day = date('N') - 1;

        // Maand bepalen
        $day = date('N') - 1;
        $month = date('m', strtotime('+' . (7 - $day - 1) . ' days'));
        $year = date('Y', strtotime('+' . (7 - $day - 1) . ' days'));

        $week_start = date('Y-m-d', strtotime('-' . $day . ' days'));
        $week_end = date('Y-m-d', strtotime('+' . (7 - $day) . ' days'));

        // Jump naar eerste dag vd maand
        $day = new \DateTime($year . '-' . $month . '-01');
        $first_datetime_string = $day->format('c');

        // keep running back until we reach a monday
        $wkday = $day->format('N') - 1;
        $day = $day->modify('-' . $wkday . ' days');

        // Start adding to our array
        $data = array();

        // 0 = maandag, 6 = zondag
        // i.p.v. elke keer te berekenen
        $weekday = 0;

        $week = -1;

        $today = date('Ymd');

        // Blijf herhalen tot we aan een dag komen in een week zonder dagen in deze maand
        while ($week < 4 || $day->format('m') == $month || $weekday != 0) {
            if ($weekday == 0) {
                $week++;
                $data[] = array('is_selected' => false, 'days' => array());
            }
            $is_today = ($today == $day->format('Ymd'));

            $data[count($data) - 1]['days'][] = array(
                'day' => $day->format('j'),
                'is_today' => $is_today,
                'is_current_month' => ($day->format('m') == $month),
                'datetime' => $day->format('Y-m-d'),
            );

            if ($is_today) {
                $data[count($data) - 1]['is_selected'] = true;
            }

            // Volgende klaar zetten
            $day = $day->modify('+1 day');
            $weekday = ($weekday + 1) % 7;

        }

        return Template::render('pages/maandplanning/kalender',
            array(
                'days' => $this->getRawEvents($week_start, $week_end),
                'calendar' => array(
                    'weeks' => $data,
                    'month' => ucfirst(Localization::getMonth($month)),
                    'datetime' => $first_datetime_string,
                ),
            )
        );
    }

}
