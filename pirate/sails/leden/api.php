<?php

namespace Pirate\Sails\Leden;
use Pirate\Wheel\Page;
use Pirate\Wheel\Route;

class LedenApiRouter extends Route {
    function doMatch($url, $parts) {
        if ($parts[0] == 'search' && isset($_GET['q'])) {
            // Formaat nog verifieren!!
            return true;
        }
        return false;
    }

    function getPage($url, $parts) {
        require(__DIR__.'/api/search.php');
        return new Api\Search($_GET['q']);
    }
}