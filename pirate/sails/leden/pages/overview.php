<?php
namespace Pirate\Sail\Leden\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

class Overview extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        return Template::render('leden/overview', array());
    }
}