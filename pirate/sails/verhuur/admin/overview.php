<?php
namespace Pirate\Sail\Verhuur\Admin;

use Pirate\Model\Verhuur\Reservatie;
use Pirate\Page\Page;
use Pirate\Template\Template;

class Overview extends Page
{
    public $future_only = true;

    // Data voor de kalender
    private $data = array();

    public function __construct($future_only)
    {
        $this->future_only = $future_only;
    }

    public function customHeaders()
    {
        if (isset($_GET["csv"])) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="verhuurkalender.csv"');
        }

        return false; // keep statuscode
    }

    public function getStatusCode()
    {
        return 200;
    }

    // Voegt de maand toe als die nog niet in de data zou zitten
    private function addMonthForDate($datetime)
    {
        $current = null;

        if (count($this->data) > 0) {
            $current = $this->data[count($this->data) - 1]['m'];
        }

        $eventMonth = $datetime->format('n');
        if (count($this->data) == 0 || $eventMonth !== $current) {
            $this->data[] = array('month' => ucfirst(datetimeToMonthYear($datetime)), 'm' => $eventMonth, 'reservaties' => array());
        }
    }

    private function addReservatie($reservatie)
    {
        $this->addMonthForDate($reservatie->startdatum);

        // Nu kunnen we ons event rustig toevoegen in de events array
        $this->data[count($this->data) - 1]['reservaties'][] = array(
            'type' => 'reservatie',
            'reservatie' => $reservatie,
            'date' => $this->getDateString($reservatie->startdatum, $reservatie->einddatum),
        );
    }

    public function getDateString($start, $end)
    {
        return ucfirst(datetimeToShortWeekday($start)) . ' ' . $start->format('d/m')
        . ' tot '
        . datetimeToShortWeekday($end) . ' ' . $end->format('d/m');
    }

    private function addEmpty($mondayAfter)
    {
        $sunday = clone $mondayAfter;
        $sunday->modify('-1 day');

        $friday = clone $mondayAfter;
        $friday->modify('-3 day');

        $this->addMonthForDate($friday);

        // Nu kunnen we ons event rustig toevoegen in de events array
        $this->data[count($this->data) - 1]['reservaties'][] = array(
            'type' => 'empty',
            'date' => $this->getDateString($friday, $sunday),
        );
    }

    public function calculateVerhuurkalender($reservaties)
    {
        $this->data = array();

        $day = date('N') - 1;

        // Einde v/d huidige week (= maandag!!) als start datum
        // todo: future_only fix
        $day = new \DateTime(date('Y-m-d', strtotime('+' . (7 - $day) . ' days')) . ' 00:00');

        // Maand berekenen van de maandag van deze week
        $current_week = clone $day;
        $current_week->modify('-7 day');
        $month = $current_week->format('n');
        $months = 1;
        $latest_date = clone $day;

        // Eerste 2 maand
        while (count($reservaties) > 0 || $months <= 3) {
            $has_events = false;

            // Kijken of we evenementen hebben voor $day die we nog niet hebben gefiltert
            while (count($reservaties) > 0 && $reservaties[0]->startdatum < $day) {
                $reservatie = array_shift($reservaties);
                if ($reservatie->ligt_vast) {
                    $has_events = true;
                    $this->addReservatie($reservatie);
                    if ($reservatie->einddatum > $latest_date) {
                        $latest_date = $reservatie->einddatum;
                    }
                }
            }

            // Toevoegen
            if (!$has_events) {
                $friday = clone $day;
                $friday->modify('-3 day');

                if ($friday > $latest_date) {
                    $this->addEmpty($day);
                }
            }

            // Volgende maandag 00:00 klaar zetten, als dit de 2e andere maand is, stoppen we
            $current_week = clone $day;
            $m = $current_week->format('n');
            $day = $day->modify('+7 days'); // einddatum (!= maand die we gaan tonen)
            if ($m != $month) {
                $month = $m;
                $months++;
            }
        }
    }

    public function hasOwnLayout()
    {
        if (isset($_GET["csv"])) {
            return true;
        }
        return false;
    }

    public function getContent()
    {
        $data_behandeling = array();

        $reservaties = Reservatie::getReservatiesOverview($this->future_only);

        foreach ($reservaties as $reservatie) {
            if ($reservatie->door_leiding) {
                continue;
            }

            if ($reservatie->ligt_vast) {
                continue;
            }

            $data_behandeling[] = $reservatie;
        }

        $this->calculateVerhuurkalender($reservaties);

        if (isset($_GET["csv"])) {
            $str = "Nummer;Datum;Groepsnaam;Naam verantwoordelijke;E-mail;Telefoon;Huur betaald;Waarborg betaald\n";

            foreach ($this->data as $maand) {
                $str .= ';' . $maand['month'] . ";;;;;;\n";
                foreach ($maand["reservaties"] as $event) {
                    $date = $event['date'];
                    if ($event["type"] == "reservatie") {
                        $reservatie = $event['reservatie'];
                        $str .= "$reservatie->contract_nummer;$date;$reservatie->groep;$reservatie->contact_naam;$reservatie->contact_email;" . $reservatie->getExcelSafeTelephone() . ';';
                        if ($reservatie->huur_betaald) {
                            $str .= $reservatie->getHuur(true);
                        }
                        $str .= ";";
                        if ($reservatie->waarborg_betaald) {
                            $str .= $reservatie->getWaarborg(true);
                        }
                        $str .= "\n";
                        //Huur betaald;Waarborg betaald\n";
                    } else {
                        $str .= ";$date;Vrij;;;;;\n";
                    }
                }
            }
            return $str;
        }

        return Template::render('admin/verhuur/overview', array(
            'months' => $this->data,
            'in_behandeling' => $data_behandeling,
            'future_only' => $this->future_only,
        ));
    }
}
