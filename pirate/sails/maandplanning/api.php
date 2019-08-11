<?php

namespace Pirate\Sails\Maandplanning;
use Pirate\Wheel\Page;
use Pirate\Wheel\Route;

class MaandplanningApiRouter extends Route {
    function doMatch($url, $parts) {

        if ($parts[0] == 'events-between') {
            // Formaat nog verifieren!!
            return true;
        }
        if ($parts[0] == 'search') {
            // Formaat nog verifieren!!
            return true;
        }
        return false;
    }

    function getPage($url, $parts) {

        if ($parts[0] == 'search') {
            require(__DIR__.'/api/search.php');
            return new Api\Search($_GET['q']);
        }

        require(__DIR__.'/api/events-between.php');
        return new Api\EventsBetween($parts[1], $parts[2]);
    }
}