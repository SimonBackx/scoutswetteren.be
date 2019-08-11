<?php
namespace Pirate\Sails\Leden\Pages;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;

class BuitenInschrijvingenPeriode extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        return Template::render('pages/leden/buiten-inschrijvingen-periode', array());
    }
}