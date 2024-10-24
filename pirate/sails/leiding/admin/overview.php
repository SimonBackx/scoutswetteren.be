<?php
namespace Pirate\Sails\Leiding\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Leiding\Models\Leiding;

class Overview extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $data = array();

        $leiding = Leiding::getLeiding();

        $leiding_zichtbaar = Leiding::isLeidingZichtbaar();
        $leidingsverdeling = Leiding::getLeidingsverdeling();


        foreach ($leiding as $leider) {
            $group = array(
                'name' => '',
                'leiding' => array()
            );

            if (isset($leider->tak)) {
                $group['name'] = ucfirst($leider->tak);
            } else {
                $group['name'] = 'Losse leden / oudercomité';
            }

            if (!isset($data[count($data)-1]) || $data[count($data)-1]['name'] !== $group['name']) {
                $data[] = $group;
            }

            $data[count($data)-1]['leiding'][] = $leider;
            
        }

        return Template::render('admin/leiding/overview', array(
            'groups' => $data,
            'leidingsverdeling' => $leidingsverdeling,
            'leiding_zichtbaar' => $leiding_zichtbaar
        ));
    }
}