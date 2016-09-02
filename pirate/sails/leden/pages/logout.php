<?php
namespace Pirate\Sail\Leden\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leden\Ouder;

class Logout extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        Ouder::logout();
        header("Location: https://".$_SERVER['SERVER_NAME']."/");

        return Template::render('leiding/logout', array(
            'description' => 'Uitloggen'
        ));
    }
}