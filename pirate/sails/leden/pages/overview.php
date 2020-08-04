<?php
namespace Pirate\Sails\Leden\Pages;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Sails\Users\Models\User;
use Pirate\Sails\Leden\Models\Inschrijving;

class Overview extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        return Template::render('pages/leden/overview', array(
            'logged_in' => !Ouder::isLoggedIn() && User::isLoggedIn(),
            'is_voorinschrijven' => Inschrijving::isVoorinschrijven(),
            'voorinschrijven_date' => Inschrijving::getVoorinschrijvenDate(),
        ));
    }
}