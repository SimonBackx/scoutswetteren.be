<?php
namespace Pirate\Sail\Leden\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

class NieuwLid extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {

        return Template::render('leden/nieuw-lid', array(
            'leden' => array()
        ));
    }
}