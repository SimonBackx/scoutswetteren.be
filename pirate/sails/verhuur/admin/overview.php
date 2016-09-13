<?php
namespace Pirate\Sail\Verhuur\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Verhuur\Reservatie;

class Overview extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $data = array();

        $data_behandeling = array();

        $reservaties = Reservatie::getReservatiesOverview();
        foreach ($reservaties as $reservatie) {
            $group = array(
                'name' => '',
                'reservaties' => array()
            );
            $today = new \DateTime();
            $difference = $reservatie->startdatum->diff($today);
            $days = $difference->days;

            $difference = $today->diff($reservatie->aanvraag_datum);
            $days_aanvraag = $difference->days;

            if ($reservatie->goedgekeurd === null) {
                $group['name'] = 'Nieuwe aanvraag';
            } elseif (!$reservatie->contract_ondertekend) {
                $group['name'] = 'Contract niet ondertekend';
            } elseif (!$reservatie->ligt_vast) {
                $group['name'] = 'Ligt niet vast in kalender';
            } elseif (($days_aanvraag > 14 || $days < 14) && $reservatie->waarborg_betaald === false) {
                $group['name'] = 'Waarborg nog niet betaald';
            } elseif (!$reservatie->huur_betaald && $days < 30) {
                $group['name'] = 'Huur nog niet betaald';
            } else {
                continue;
            }

            if (!isset($data_behandeling[count($data_behandeling)-1]) || $data_behandeling[count($data_behandeling)-1]['name'] !== $group['name']) {
                $data_behandeling[] = $group;
            }

            $data_behandeling[count($data_behandeling)-1]['reservaties'][] = $reservatie;
            
        }

        foreach ($reservaties as $reservatie) {
            $group = array(
                'name' => '',
                'reservaties' => array()
            );

            if ($reservatie->ligt_vast) {
                $group['name'] = ucfirst(datetimeToMonthYear($reservatie->startdatum));
            } else {
                continue;
            }

            if (!isset($data[count($data)-1]) || $data[count($data)-1]['name'] !== $group['name']) {
                $data[] = $group;
            }

            $data[count($data)-1]['reservaties'][] = $reservatie;
            
        }


        return Template::render('verhuur/admin/overview', array(
            'groups' => $data,
            'in_behandeling' => $data_behandeling
        ));
    }
}