<?php

namespace Pirate\Sail\Verhuur;
use Pirate\Page\Page;
use Pirate\Route\Route;

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