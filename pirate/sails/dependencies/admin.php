<?php
namespace Pirate\Sail\Dependencies;
use Pirate\Page\Page;
use Pirate\Route\AdminRoute;
use Pirate\Model\Leiding\Leiding;

class DependenciesAdminRouter extends AdminRoute {
    private $leiding = null;

    static function getAvailablePages() {
        return [];
    }

    function doMatch($url, $parts) {
        if (!Leiding::hasPermission('webmaster')) {
            return false;
        }
        
        if ($url == 'dependencies') {
            return true;
        }
        
        return false;
    }

    function getPage($url, $parts) {
        require(__DIR__.'/admin/overview.php');
        return new Admin\Overview();
    }
}