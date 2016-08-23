<?php
namespace Pirate\Sail\Leiding\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

class Gegevens extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        return '<h1>Hier kunnen gegevens bewerkt worden (later).</h1>';
        /*return Template::render('leiding/admin', array(
            'content' => $this->adminPage->getContent(),
            'admin' => array(
                'selected' => $this->selected
            )
        ));*/
    }
}