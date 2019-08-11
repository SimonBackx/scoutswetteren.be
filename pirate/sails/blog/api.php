<?php

namespace Pirate\Sails\Blog;
use Pirate\Wheel\Page;
use Pirate\Wheel\Route;

class BlogApiRouter extends Route {
    function doMatch($url, $parts) {

        if ($parts[0] == 'get-page') {
            // Formaat nog verifieren!!
            return true;
        }
        if ($parts[0] == 'search') {
            // Formaat nog verifieren!!
            return true;
        }
        return false;
    }

    function getPage($url, $parts) {

        if ($parts[0] == 'search') {
            require(__DIR__.'/api/search.php');
            return new Api\Search($_GET['q']);
        }

        require(__DIR__.'/api/get-page.php');
        return new Api\GetPage(max(1,intval($parts[1])));
    }
}