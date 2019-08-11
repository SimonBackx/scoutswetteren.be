<?php
namespace Pirate\Sails\Leiding;

use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Wheel\Route;

class LeidingRouter extends Route
{
    private $adminPage = null;

    public function doMatch($url, $parts)
    {
        if ($parts[0] == 'admin') {
            if (Leiding::isLoggedIn()) {
                include __DIR__ . '/../_bindings/admin.php';

                if (!isset($admin_routes)) {
                    echo 'Admin route bindings not found';
                    exit;
                }

                array_shift($parts);
                $url = implode('/', $parts);

                foreach ($admin_routes as $module) {
                    $ucfirst_module = ucfirst($module);
                    require_once __DIR__ . "/../$module/admin.php";
                    $classname = "\\Pirate\\Sails\\$ucfirst_module\\{$ucfirst_module}AdminRouter";

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

    public function getPage($url, $parts)
    {
        // Admin pagina
        require __DIR__ . '/pages/admin.php';
        $sail = '';
        if (isset($parts[1])) {
            $sail = $parts[1];
        }

        return new Pages\Admin($this->adminPage, $sail);
    }
}
