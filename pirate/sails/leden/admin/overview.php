<?php
namespace Pirate\Sail\Leden\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Inschrijving;

class Overview extends Page {
    public $tak = '';
    function __construct($tak = '') {
        $this->tak = $tak;
    }
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $user = Leiding::getUser();

        $tak = $this->tak;
        $takken = Inschrijving::$takken;

        if (empty($tak) && !empty($user->tak)) {
            $tak = $user->tak;
        }

        $leden = Lid::getLedenForTak($tak);


        return Template::render('leden/admin/overview', array(
            'leden' => $leden,
            'takken' => $takken,
            'tak' => $tak
        ));
    }
}