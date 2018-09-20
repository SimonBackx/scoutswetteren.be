<?php
namespace Pirate\Sail\Leden\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Leden\Gezin;
use Pirate\Database\Database;
use Pirate\Mail\Mail;

// Deze pagina mag enkel getoond worden als de ouder (tijdelijk) ingelogd is
class UitzonderingToelaten extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        Lid::setLimitsIgnored(true);

        return Template::render('leden/uitzondering-toelaten', array());
    }
}