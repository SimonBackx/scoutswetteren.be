<?php
namespace Pirate\Sail\Homepage;
use Pirate\Page\Page;
use Pirate\Route\Route;

class HomepageRouter extends Route {
    function doMatch($url) {
        return true;
    }

    function getPage($url) {
        require(__DIR__.'/pages/homepage.php');
        return new Pages\Homepage();
    }
}