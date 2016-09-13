<?php
namespace Pirate\Sail\Leiding;
use Pirate\Page\Page;
use Pirate\Route\Route;
use Pirate\Model\Leiding\Leiding;

class LeidingRouter extends Route {
    private $adminPage = null;

    function doMatch($url, $parts) {
        if (count($parts) >= 1 && $parts[0] == 'leiding') {
            // Beveiligde sectie
            if (count($parts) == 3 && ($parts[1] == 'set-password')) {
                // Key controleren en tijdelijk inloggen
                if (Leiding::temporaryLoginWithPasswordKey($parts[2])) {
                    return true;
                }
                return false;
            }
        }
        if ($url == 'login') {
            if (!Leiding::isLoggedIn())
                return true;
        }
        elseif ($url == 'logout') {
            if (Leiding::isLoggedIn())
                return true;
        }elseif ($parts[0] == 'admin') {
            if (Leiding::isLoggedIn()) {
                include(__DIR__.'/../_bindings/admin.php');

                if (!isset($admin_routes)) {
                    echo 'Admin route bindings not found';
                    exit;
                }

                array_shift($parts);
                $url = implode('/', $parts);

                foreach ($admin_routes as $module) {
                    $ucfirst_module = ucfirst($module);
                    require(__DIR__."/../$module/admin.php");
                    $classname = "\\Pirate\\Sail\\$ucfirst_module\\{$ucfirst_module}AdminRouter";

                    $router = new $classname();
                    if ($router->doMatch($url, $parts)) {
                        $this->adminPage = $router->getPage($url, $parts);
                        return true;
                    }
                }
            }
        }

        return false;
    }

    function getPage($url, $parts) {
        if ($url == 'login') {
            require(__DIR__.'/pages/login.php');
            return new Pages\Login();
        }
        if ($url == 'logout') {
            require(__DIR__.'/pages/logout.php');
            return new Pages\Logout();
        }
        if ($parts[0] == 'admin') {

            // Admin pagina
            require(__DIR__.'/pages/admin.php');
            $sail = '';
            if (isset($parts[1]))
                $sail = $parts[1];
            return new Pages\Admin($this->adminPage, $sail);
        }
        require(__DIR__.'/pages/set-password.php');
        return new Pages\SetPassword();
    }
}