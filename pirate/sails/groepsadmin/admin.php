<?php
namespace Pirate\Sail\Groepsadmin;
use Pirate\Page\Page;
use Pirate\Route\AdminRoute;

class GroepsadminAdminRouter extends AdminRoute {
    static function getAvailablePages() {
        return [];
    }

    function doMatch($url, $parts) {
        if ($url == 'groepsadmin') {
            return true;
        }

        return false;
    }

    function getPage($url, $parts) {
        require(__DIR__.'/admin/check.php');
        return new Admin\Check();
    }
}