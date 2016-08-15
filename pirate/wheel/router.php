<?php
namespace Pirate\Route;
use Pirate\Page\Page404;

class Router {
    function route($url) {
        // Load the page and route object
        // These will get extended by other objects we depend on
        require(__DIR__.'/page.php');
        require(__DIR__.'/block.php');
        require(__DIR__.'/route.php');

        // This part needs to get rewritten and loaded dynamically
        // based on the sails that are present.
        
        // Route 'website' available in Website sail
        require(__DIR__.'/../sails/homepage/routes.php');
        $website = new \Pirate\Sail\Homepage\HomepageRouter();
        if ($website->doMatch($url)) {
            return $website->getPage($url);
        }

        // Default
        return new Page404();
    }
}

