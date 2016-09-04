<?php
namespace Pirate\Sail\Leden\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Inschrijving;

class EditLid extends Page {
    private $id;

    function __construct($id) {
        $this->id = $id;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        /*$user = Leiding::getUser();

        $tak = '';
        if (!empty($user->tak)) {
            $tak = $user->tak;
        } 

        // TODO: aanpassen zodat evenementen uit de huidige week, VOOR vandaag ook worden meegegeven
        $leden = Lid::getLedenForTak($tak);

        return Template::render('leden/admin/overview', array(
            'leden' => $leden,
            'tak' => $tak
        ));*/
        return '<p>WIP</p>';
    }
}