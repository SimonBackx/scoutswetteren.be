<?php
namespace Pirate;
use \Pirate\Database\Database;

class Ship {
    function sail() {
        global $config;
        
        date_default_timezone_set('Europe/Brussels');

        // Catch all errors and warnings
        // 
        // 

        ob_start();
        require(__DIR__.'/config.php');

        // Loading all builtin stuff
        require(__DIR__.'/template.php');
        require(__DIR__.'/database.php');
        require(__DIR__.'/model.php');

        // Loading Sails's services with certain priority level
        Database::init();

        // Load router
        require(__DIR__.'/router.php');
        $router = new Route\Router();
        $page = $router->route('/test');

        // Return the page, set the status code etc.
        // 
        
        // Errors nu pas doorsturen zodat we in tussentijd headers etc kunnen aanpassen
        $errors = ob_get_contents();
        ob_end_clean();
        echo $errors;
        $page->execute();

    }
}