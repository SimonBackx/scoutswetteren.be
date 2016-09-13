<?php
namespace Pirate\Sail\Leiding\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;

class Overview extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $data = array();

        $leiding = Leiding::getLeiding();

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


        return Template::render('leiding/admin/overview', array(
            'groups' => $data
        ));
    }
}