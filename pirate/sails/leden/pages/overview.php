<?php
namespace Pirate\Sail\Leden\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Users\User;

class Overview extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        return Template::render('leden/overview', array(
            'logged_in' => !Ouder::isLoggedIn() && User::isLoggedIn()
        ));
    }
}