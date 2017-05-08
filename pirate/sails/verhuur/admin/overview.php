<?php
namespace Pirate\Sail\Verhuur\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Verhuur\Reservatie;

class Overview extends Page {
    public $future_only = true;

    function __construct($future_only) {
        $this->future_only = $future_only;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $data = array();

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
            'in_behandeling' => $data_behandeling,
            'future_only' => $this->future_only
        ));
    }
}