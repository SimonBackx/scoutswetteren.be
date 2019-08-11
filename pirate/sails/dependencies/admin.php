<?php
namespace Pirate\Sails\Dependencies;
use Pirate\Wheel\Page;
use Pirate\Wheel\AdminRoute;
use Pirate\Sails\Leiding\Models\Leiding;

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