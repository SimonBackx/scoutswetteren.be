<?php
namespace Pirate\Sail\Verhuur;
use Pirate\Page\Page;
use Pirate\Route\Route;

class VerhuurRouter extends Route {
    private $adminPage = null;

    function doMatch($url, $parts) {
        if ($url == 'verhuur') {
            return true;
        }
        if ($url == 'verhuur/reserveren') {
            return true;
        }
        if ($url == 'verhuur/materiaal') {
            return true;
        }
       
        return false;
    }

    function getPage($url, $parts) {
        if ($url == 'verhuur') {
            require(__DIR__.'/pages/verhuur.php');
            return new Pages\Verhuur();
        }
        if ($url == 'verhuur/materiaal') {
            require(__DIR__.'/pages/materiaal.php');
            return new Pages\Materiaal();
        }
        
        require(__DIR__.'/pages/verhuur-reserveren.php');
        return new Pages\VerhuurReserveren();
    }
}