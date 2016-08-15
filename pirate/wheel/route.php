<?php
namespace Pirate\Route;
use Pirate\Page\Page404;

class Route {
    function doMatch($url) {
        return false;
    }

    function getPage($url) {
        return new Page404();
    }
}