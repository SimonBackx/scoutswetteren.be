<?php
namespace Pirate\Sail\Leiding;
use Pirate\Page\Page;
use Pirate\Route\Route;
use Pirate\Model\Leiding\Leiding;

class LeidingRouter extends Route {
    function doMatch($url, $parts) {
        if ($url == 'login') {
            if (!Leiding::isLoggedIn())
                return true;
        }
        return false;
    }

    function getPage($url, $parts) {
        require(__DIR__.'/pages/login.php');
        return new Pages\Login();
    }
}