<?php
namespace Pirate\Sail\Homepage;
use Pirate\Page\Page;
use Pirate\Route\Route;

class HomepageRouter extends Route {
    function doMatch($url, $parts) {
        if ($url == '') {
            return true;
        }
        if ($url == 'sponsors') {
            return true;
        }

        if ($url == 'privacy') {
            return true;
        }
        return false;
    }

    function getPage($url, $parts) {
        if ($url == 'sponsors') {
            require(__DIR__.'/pages/sponsors.php');
            return new Pages\Sponsors();
        }

        if ($url == 'privacy') {
            require(__DIR__.'/pages/privacy.php');
            return new Pages\Privacy();
        }
        require(__DIR__.'/pages/homepage.php');
        return new Pages\Homepage();
    }
}