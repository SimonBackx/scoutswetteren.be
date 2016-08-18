<?php

namespace Pirate\Sail\Blog;
use Pirate\Page\Page;
use Pirate\Route\Route;

class BlogApiRouter extends Route {
    function doMatch($url) {
        $parts = explode('/', $url);

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

    function getPage($url) {
        $parts = explode('/', $url);

        if ($parts[0] == 'search') {
            require(__DIR__.'/api/search.php');
            return new Api\Search($_GET['q']);
        }

        require(__DIR__.'/api/get-page.php');
        return new Api\GetPage(max(1,intval($parts[1])));
    }
}