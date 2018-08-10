<?php
namespace Pirate\Route;
use Pirate\Page\Page404;

class Route {
    function doMatch($url, $parts) {
        return false;
    }

    function getPage($url, $parts) {
        return new Page404();
    }
}

class AdminRoute extends Route {
    /**
     * Geef een lijst van alle available pages terug per permission.
     * Permission '' is voor iedereen
     */
    static function getAvailablePages() {
        return [];
    }
}
