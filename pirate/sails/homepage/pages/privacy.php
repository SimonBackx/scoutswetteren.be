<?php
namespace Pirate\Sail\Homepage\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

class Privacy extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        return Template::render('privacy', array());
    }
}