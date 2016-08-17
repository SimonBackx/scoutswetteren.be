<?php

namespace Pirate\Sail\Maandplanning;
use Pirate\Page\Page;
use Pirate\Route\Route;

class MaandplanningApiRouter extends Route {
    function doMatch($url) {
        $parts = explode('/', $url);

        if ($parts[0] == 'events-between') {
            // Formaat nog verifieren!!
            return true;
        }
        return false;
    }

    function getPage($url) {
        $parts = explode('/', $url);

        require(__DIR__.'/api/events-between.php');
        return new Api\EventsBetween($parts[1], $parts[2]);
    }
}