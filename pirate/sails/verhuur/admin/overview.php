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

        $reservaties = Reservatie::getReservatiesOverview();
        foreach ($reservaties as $reservatie) {
            $group = array(
                'name' => '',
                'reservaties' => array()
            );
            if (empty($reservatie->goedgekeurd )) {
                $group['name'] = 'Nieuw';
            } elseif ($reservatie->ligt_vast == 0) {
                $group['name'] = 'In behandeling';
            } else {
                $group['name'] = ucfirst(datetimeToMonthYear($reservatie->startdatum));
            }

            if (!isset($data[count($data)-1]) || $data[count($data)-1]['name'] != $group['name']) {
                $data[] = $group;
            }

            $data[count($data)-1]['reservaties'][] = $reservatie;
            
        }

        return Template::render('verhuur/admin/overview', array(
            'groups' => $data
        ));
    }
}