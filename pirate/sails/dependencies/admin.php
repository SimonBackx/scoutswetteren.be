<?php
namespace Pirate\Sail\Dependencies;
use Pirate\Page\Page;
use Pirate\Route\Route;
use Pirate\Model\Leiding\Leiding;

class DependenciesAdminRouter extends Route {
    private $leiding = null;

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