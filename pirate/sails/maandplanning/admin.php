<?php

namespace Pirate\Sail\Maandplanning;
use Pirate\Page\Page;
use Pirate\Route\Route;

class MaandplanningAdminRouter extends Route {
    function doMatch($url, $parts) {

        if (isset($parts[0]) && $parts[0] == 'maandplanning') {
            return true;
        }
        return false;
    }

    function getPage($url, $parts) {
        require(__DIR__.'/admin/overview.php');
        return new Admin\Overview();
    }
}