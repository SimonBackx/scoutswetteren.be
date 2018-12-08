<?php
namespace Pirate\Route;
use Pirate\Page\Page404;
use Pirate\Page\Page301;
use Pirate\Page\Page302;
use Pirate\Page\Page;

class Router {
    function route($url) {
        // Load the page and route object
        // These will get extended by other objects we depend on
        require(__DIR__.'/page.php');
        require(__DIR__.'/block.php');
        require(__DIR__.'/route.php');
        Page::setupAutoload();

        // This part needs to get rewritten and loaded dynamically
        // based on the sails that are present.
        
        // Route 'website' available in Website sail
        // check api used
        $parts = explode('/', $url);
        if ($parts[0] == 'api') {
            include(__DIR__.'/../sails/_bindings/api.php');
            if (!isset($api_routes)) {
                echo 'Api Route bindings not found';
                exit;
            }
            array_shift($parts);
            $module = array_shift($parts);
            $url = implode('/', $parts);

            if (in_array($module, $api_routes)) {
                $ucfirst_module = ucfirst($module);
                require_once(__DIR__."/../sails/$module/api.php");
                $classname = "\\Pirate\\Sail\\$ucfirst_module\\{$ucfirst_module}ApiRouter";

                $router = new $classname();

                

                if ($router->doMatch($url, $parts)) {
                    return $router->getPage($url, $parts);
                }
            }
        } else {
            $redirects = array(
                'index.php/verhuur' => '/verhuur',
                'index.php/takken' => '',
                'index.php/info' => '/info',
                'index.php/takken/kapoenen' => '/info/kapoenen',
                'index.php/takken/wouters' => '/info/wouters',
                'index.php/takken/jonggivers' => '/info/jonggivers',
                'index.php/takken/givers' => '/info/givers',
                'index.php/takken/jin' => '/info/jin',
                'index.php/contact' => '/contact',
                'index.php/info/leiding/kapoenen' => '/info/kapoenen',
                'index.php/info/leiding/wouters' => '/info/wouters',
                'index.php/info/leiding/jonggivers' => '/info/jonggivers',
                'index.php/info/leiding/givers' => '/info/givers',
                'index.php/info/leiding/jin' => '/info/jin',
                'index.php/foto-s' => '/fotos'
            );
            if (in_array($url, array_keys($redirects))) {
                header("Location: https://".$_SERVER['SERVER_NAME'].$redirects[$url]);
                return new Page301();
            }

            if ($url == 'inschrijven-winterfeest') {
                header("Location: https://docs.google.com/forms/d/e/1FAIpQLSd2fOMUuwnmmj9PElKAHplFkYeezq5SpGcKTZtplGKFdW9F-g/viewform?usp=pp_url&entry.68005830&entry.1905493851&entry.123158276=0&entry.372456103=0&entry.1007462089=0&entry.1496954756=0&entry.1369601917=0&entry.783841059=0&entry.710634492=0&entry.1759246294=0&entry.402550269=0&entry.1797343913=0&entry.1508497080=0&entry.2115967023=0");
                return new Page302();
            }

            include(__DIR__.'/../sails/_bindings/routes.php');
            if (!isset($routes)) {
                echo 'Route bindings not found';
                exit;
            }

            foreach ($routes as $module) {
                $ucfirst_module = ucfirst($module);
                require_once(__DIR__."/../sails/$module/routes.php");
                $classname = "\\Pirate\\Sail\\$ucfirst_module\\{$ucfirst_module}Router";

                $router = new $classname();
                if ($router->doMatch($url, $parts)) {
                    return $router->getPage($url, $parts);
                }
            }
        }

        // Default
        return new Page404();
    }


}

