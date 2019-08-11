<?php

namespace Pirate\Sails\Verhuur;
use Pirate\Wheel\Page;
use Pirate\Wheel\Route;

class VerhuurApiRouter extends Route {
    function doMatch($url, $parts) {

        if (count($parts) == 3 && $parts[0] == 'kalender') {
            // Formaat nog verifieren!!
            return true;
        }
        return false;
    }

    function getPage($url, $parts) {
        require(__DIR__.'/api/kalender.php');
        return new Api\Kalender($parts[1], $parts[2]);
    }
}