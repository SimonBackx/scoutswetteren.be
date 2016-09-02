<?php
namespace Pirate\Sail\Leden\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Leden\Steekkaart;
use Pirate\Database\Database;

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
        return Template::render('leden/afrekening', array(
            'afrekening' => $this->afrekening,
            'finished' => $finished
        ));
    }
}