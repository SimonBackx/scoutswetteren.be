<?php
namespace Pirate;
use \Pirate\Database\Database;
use Pirate\Model\Model;

class Ship {
    function sail() {
        global $config;

        if ($_SERVER['SERVER_PORT'] != 443) {
            die('Server gebruikt geen HTTPS.');
        }

        date_default_timezone_set('Europe/Brussels');
        setlocale(LC_MONETARY, 'nl_BE');

        // Catch all errors and warnings
        // 
        // 

        ob_start();
        require(__DIR__.'/config.php');

        // Loading all builtin stuff
        require(__DIR__.'/template.php');
        require(__DIR__.'/database.php');
        require(__DIR__.'/model.php');
        require(__DIR__.'/mail.php');

        // Loading Sails's services with certain priority level
        Database::init();

        // Load router
        require(__DIR__.'/router.php');
        $router = new Route\Router();

        // autoloader voor models laden:
        Model::setupAutoload();

        if (!isset($_GET['route'])) {
            $_GET['route'] = '';
        }
        $page = $router->route($_GET['route']);

        // Return the page, set the status code etc.
        // 
        
        // Errors nu pas doorsturen zodat we in tussentijd headers etc kunnen aanpassen
        $errors = ob_get_contents();
        ob_end_clean();
        echo $errors;
        $page->execute();

        $page->goodbye();

    }
}