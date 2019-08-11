<?php
namespace Pirate\Sails\Leden\Pages;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Sails\Leden\Models\Steekkaart;
use Pirate\Wheel\Database;

class ViewAfrekening extends Page {
    private $afrekening;

    function __construct($afrekening) {
        $this->afrekening = $afrekening;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        // Kijken of de steekkaart al geset is
        $finished = false;
        if (isset($_GET['klaar'])) {
            $finished = true;
        }
        return Template::render('pages/leden/afrekening', array(
            'afrekening' => $this->afrekening,
            'finished' => $finished
        ));
    }
}