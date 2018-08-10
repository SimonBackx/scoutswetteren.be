<?php

namespace Pirate\Sail\Leden;
use Pirate\Page\Page;
use Pirate\Route\Route;

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