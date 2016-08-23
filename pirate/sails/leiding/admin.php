<?php

namespace Pirate\Sail\Leiding;
use Pirate\Page\Page;
use Pirate\Route\Route;

class LeidingAdminRouter extends Route {
    function doMatch($url, $parts) {
        if (empty($url)) {
            return true;
        }
        return false;
    }

    function getPage($url, $parts) {
        require(__DIR__.'/admin/gegevens.php');
        return new Admin\Gegevens();
    }
}