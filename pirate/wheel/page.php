<?php
namespace Pirate\Page;

class Page {
    function getStatusCode() {
        return 200;
    }

    // Geeft layout object waarin het gerenderd moet worden
    function getLayout() {

    }

    function getContent() {
        return 'getContent method not implemented';
    }

    // Voor een zijmenu bijvoorbeeld
    function getSide() {}

    // Array die layout kan gebruiken voor extra eigenschappen
    // (bv doorzichtigheid van het menu)
    function getLayoutData() {
        return array();
    }

    final function execute() {
        http_response_code($this->getStatusCode());
        echo $this->getContent();
    }

}

class Page404 extends Page {
    function getStatusCode() {
        return 404;
    }

    function getContent() {
        return 'Page not found';
    }
}