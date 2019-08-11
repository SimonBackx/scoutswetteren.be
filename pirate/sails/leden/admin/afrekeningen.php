<?php
namespace Pirate\Sails\Leden\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Leden\Models\Afrekening;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Sails\Leden\Models\Inschrijving;

class Afrekeningen extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {

        // TODO: aanpassen zodat evenementen uit de huidige week, VOOR vandaag ook worden meegegeven
        $afrekeningen = Afrekening::getAfrekeningen();

        return Template::render('admin/leden/afrekeningen', array(
            'afrekeningen' => $afrekeningen
        ));
    }
}