<?php
namespace Pirate\Sails\Leden\Pages;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Sails\Leden\Models\Gezin;
use Pirate\Wheel\Database;
use Pirate\Sails\AmazonSes\Classes\Mail;

// Deze pagina mag enkel getoond worden als de ouder (tijdelijk) ingelogd is
class UitzonderingToelaten extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        Lid::setLimitsIgnored(true);

        return Template::render('pages/leden/uitzondering-toelaten', array());
    }
}