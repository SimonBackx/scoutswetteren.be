<?php
namespace Pirate\Sail\Verhuur\Blocks;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Verhuur\Reservatie;

class Verhuurkalender extends Block {
    function getForMonth($year, $month) {
        global $config;

        // Jump naar eerste dag vd maand
        try {
            $day = new \DateTime($year.'-'.$month.'-01');
            $first_datetime_string = $day->format('c');
        } catch (\Exception $e){
            return '<p>Er ging iets mis</p>';
        }

        // keep running back until we reach a monday
        $wkday = $day->format('N')-1;
        $day = $day->modify('-'.$wkday.' days');

        $day_end = new \DateTime($year.'-'.$month.'-01');
        $day_end->modify('+1 month');

        $wkday = 7-$day_end->format('N');
        $day_end = $day_end->modify('+'.$wkday.' days');

        $reservaties = Reservatie::getReservaties($day->format('Y-m-d'), $day_end->format('Y-m-d'), true);
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
            $me = $day->format('Ymd');
            $is_today = ($today == $me);
            $disabled = false;

            while (count($reservaties) > 0 && $reservaties[0]->startdatum <= $day) {
                if ($reservaties[0]->einddatum >= $day) {
                    $disabled = true;
                    break;
                } else {
                    array_shift($reservaties);
                }
            }

            $data[count($data)-1]['days'][] = array(
                'day' => $day->format('j'),
                'is_today' => $is_today,
                'is_current_month' => ($day->format('m') == $month),
                'datetime' => $day->format('d-m-Y'),
                'disabled' => $disabled
            );

            if ($is_today) {
                $data[count($data)-1]['is_selected'] = true;
            }

            // Volgende klaar zetten
            $day = $day->modify('+1 day');
            $weekday = ($weekday + 1)%7;

        }

        return Template::render('verhuur/verhuurkalender', 
            array(
                'calendar' => array(
                    'weeks' => $data,
                    'month' => ucfirst($config['months'][$month-1]),
                    'datetime' => $first_datetime_string
                )
            )
        );
    }
    // Geeft volledige block
    function getContent() {
        $this->getMonth($year, $month);
        return $this->getForMonth($year, $month);
    }

    function getMonth(&$year, &$month) {
        $day = date('N')-1;

        // Maand bepalen
        $day = date('N')-1;
        $month = date('m', strtotime('+'.(7-$day).' days'));
        $year = date('Y', strtotime('+'.(7-$day).' days'));
    }

}