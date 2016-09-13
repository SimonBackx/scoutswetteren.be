<?php
namespace Pirate\Sail\Leden\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leden\Afrekening;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Inschrijving;

class Afrekeningen extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {

        // TODO: aanpassen zodat evenementen uit de huidige week, VOOR vandaag ook worden meegegeven
        $afrekeningen = Afrekening::getAfrekeningen();

        return Template::render('leden/admin/afrekeningen', array(
            'afrekeningen' => $afrekeningen
        ));
    }
}