<?php
namespace Pirate\Route;
use Pirate\Page\Page404;

class Route {
    function doMatch($url, $parts) {
        return false;
    }

    function getPage($url, $parts) {
        return new Page404();
    }
}