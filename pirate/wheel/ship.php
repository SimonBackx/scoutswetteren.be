<?php
namespace Pirate;

class Ship {
    function sail() {
        // Loading all builtin stuff
        require(__DIR__.'/template.php');

        // Loading Sails's services with certain priority level

        // Load router
        require(__DIR__.'/router.php');
        $router = new Route\Router();
        $page = $router->route('/test');

        // Return the page, set the status code etc.
        $page->execute();

    }
}