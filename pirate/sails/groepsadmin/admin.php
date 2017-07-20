<?php
namespace Pirate\Sail\Groepsadmin;
use Pirate\Page\Page;
use Pirate\Route\Route;

class GroepsadminAdminRouter extends Route {
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