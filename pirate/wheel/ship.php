<?php
namespace Pirate;
use \Pirate\Database\Database;
use Pirate\Model\Model;

class Ship {
    function sail() {
        global $config;

        $url = strtok($_SERVER["REQUEST_URI"],'?');
        $url = substr($url, 1);
        if (substr($url, -1) == '/') {
            // redirecten NU
            $url = substr($url, 0, strlen($url) - 1);
            $q = $_SERVER['QUERY_STRING'];
            if (strlen($q) > 0) {
                $q = '?'.$q;
            }
            http_response_code(301);
            header("Location: https://".$_SERVER['SERVER_NAME']."/".$url.$q);
            return;
        }

        if ($_SERVER['SERVER_PORT'] != 443) {
           die('Er is een probleem ontstaan waardoor de website geen beveiligde verbinding gebruikt. Neem conact met ons op (website@scoutswetteren.be) als dit probleem zich blijft voordoen.');
        }

        date_default_timezone_set('Europe/Brussels');
        setlocale(LC_MONETARY, 'nl_BE');

        // Catch all errors and warnings
        // 
        // 


        try {
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

        $page = $router->route($url);

        } catch (\Error $e) {
            echo '<p>Oeps! Er ging iets mis op de website. Neem contact op met onze webmaster (website@scoutswetteren.be) als dit probleem zich blijft voordoen.</p><pre>'.$e->getFile().' line '.$e->getLine().' '.$e->getMessage().'</pre>';
            exit;
        }

        // Return the page, set the status code etc.
        // 
        
        $page->execute();

        $page->goodbye();

    }
}