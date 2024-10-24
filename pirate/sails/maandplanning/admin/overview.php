<?php
namespace Pirate\Sails\Maandplanning\Admin;

use Pirate\Sails\Environment\Classes\Localization;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Sails\Maandplanning\Models\Event;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Overview extends Page
{
    private $data = array();

    // Voegt de maand toe als die nog niet in de data zou zitten
    private function addMonthForDate($datetime)
    {
        $current = null;

        if (count($this->data) > 0) {
            $current = $this->data[count($this->data) - 1]['m'];
        }

        $eventMonth = $datetime->format('n');
        if (count($this->data) == 0 || $eventMonth !== $current) {
            $this->data[] = array('month' => ucfirst(Localization::getMonth($eventMonth)), 'm' => $eventMonth, 'events' => array());
        }
    }

    private function addEvent($event)
    {
        $this->addMonthForDate($event->startdate);

        // Nu kunnen we ons event rustig toevoegen in de events array
        $this->data[count($this->data) - 1]['events'][] = array(
            'type' => 'event',
            'date' => ucfirst(datetimeToWeekday($event->startdate)) . ' ' . $event->startdate->format('d/m'),
            'time' => $event->startdate->format('H:i') . ' tot ' . $event->enddate->format('H:i'),
            'description' => $event->name,
            'id' => $event->id,
            'event' => $event,
        );
    }

    private function addEmpty($sunday)
    {
        $this->addMonthForDate($sunday);

        // Nu kunnen we ons event rustig toevoegen in de events array
        $this->data[count($this->data) - 1]['events'][] = array(
            'type' => 'empty',
            'date' => ucfirst(datetimeToWeekday($sunday)) . ' ' . $sunday->format('d/m'),
            'time' => '14:00',
            'full_date' => urlencode($sunday->format('d-m-Y')),
        );
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $user = Leiding::getUser();

        $tak = 'Leiding';
        $leiding = false;
        if (!empty($user->tak)) {
            $leiding = true;
            $tak = $user->tak;
        } else {
            if ($user->hasPermission('oudercomite')) {
                $tak = 'Oudercomité';
            }
        }

        $all = isset($_GET['all']);

        // TODO: aanpassen zodat evenementen uit de huidige week, VOOR vandaag ook worden meegegeven
        if ($all) {
            $events = Event::getEventsOverview();
        } else {
            $events = Event::getEventsForTak($tak);
        }

        // Sowieso eerste 2 maand tonen voor leiding
        if ($leiding) {
            $day = date('N') - 1;
            // Einde v/d huidige week (= maandag!!) als start datum
            $day = new \DateTime(date('Y-m-d', strtotime('+' . (7 - $day) . ' days')) . ' 00:00');
            $sunday = clone $day;
            $sunday->modify('-1 day');

            $month = $sunday->format('n');
            $months = 1;

            // Eerste 2 maand
            while ($months <= 3) {
                $has_events = false;

                // Kijken of we evenementen hebben voor $day die we nog niet hebben gefiltert
                while (count($events) > 0 && $events[0]->startdate < $day) {
                    $has_events = true;
                    $event = array_shift($events);
                    $this->addEvent($event);
                }

                // Toevoegen
                if (!$has_events) {
                    $sunday = clone $day;
                    $sunday->modify('-1 day');
                    $this->addEmpty($sunday);
                }

                // Volgende maandag 00:00 klaar zetten, als dit de 2e andere maand is, stoppen we
                $day = $day->modify('+7 days');
                $m = $day->format('n');
                if ($m != $month) {
                    $month = $m;
                    $months++;
                }
            }
        }

        // Overige toevoegen
        foreach ($events as $event) {
            $this->addEvent($event);
        }

        return Template::render('admin/maandplanning/overview', array(
            'months' => $this->data,
            'all' => $all
        ));
    }
}
