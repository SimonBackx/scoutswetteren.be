<?php
namespace Pirate\Route;
use Pirate\Page\Page404;
use Pirate\Page\Page301;

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
                require(__DIR__."/../sails/$module/api.php");
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

            include(__DIR__.'/../sails/_bindings/routes.php');
            if (!isset($routes)) {
                echo 'Route bindings not found';
                exit;
            }

            foreach ($routes as $module) {
                $ucfirst_module = ucfirst($module);
                require(__DIR__."/../sails/$module/routes.php");
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

