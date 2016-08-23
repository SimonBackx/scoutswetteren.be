<?php
namespace Pirate\Sail\Leiding\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;

class Logout extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        Leiding::logout();
        header("Location: https://".$_SERVER['SERVER_NAME']."/");

        return Template::render('leiding/logout', array(
            'description' => 'Uitlogpagina voor medewerkers'
        ));
    }
}