<?php
namespace Pirate\Route;

use Pirate\Classes\Environment\Environment;
use Pirate\Page\Page301;
use Pirate\Page\Page404;
use Pirate\Page\Page;

class Router
{
    public function route($url)
    {
        // Load the page and route object
        // These will get extended by other objects we depend on
        require __DIR__ . '/page.php';
        require __DIR__ . '/block.php';
        require __DIR__ . '/route.php';
        Page::setupAutoload();

        // This part needs to get rewritten and loaded dynamically
        // based on the sails that are present.

        // Route 'website' available in Website sail
        // check api used
        $parts = explode('/', $url);
        if ($parts[0] == 'api') {
            include __DIR__ . '/../sails/_bindings/api.php';
            if (!isset($api_routes)) {
                echo 'Api Route bindings not found';
                exit;
            }
            array_shift($parts);
            $module = array_shift($parts);
            $url = implode('/', $parts);

            if (in_array($module, $api_routes)) {
                $ucfirst_module = ucfirst($module);
                require_once __DIR__ . "/../sails/$module/api.php";
                $classname = "\\Pirate\\Sail\\$ucfirst_module\\{$ucfirst_module}ApiRouter";

                $router = new $classname();

                if ($router->doMatch($url, $parts)) {
                    return $router->getPage($url, $parts);
                }
            }
        } else {
            $redirects = Environment::getSetting('router.redirects', []);

            if (in_array($url, array_keys($redirects))) {
                header("Location: https://" . $_SERVER['SERVER_NAME'] . $redirects[$url]);
                return new Page301();
            }

            include __DIR__ . '/../sails/_bindings/routes.php';
            if (!isset($routes)) {
                echo 'Route bindings not found';
                exit;
            }

            foreach ($routes as $module) {
                $ucfirst_module = ucfirst($module);
                require_once __DIR__ . "/../sails/$module/routes.php";
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
