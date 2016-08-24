<?php

namespace Pirate\Sail\Maandplanning;
use Pirate\Page\Page;
use Pirate\Route\Route;

class MaandplanningAdminRouter extends Route {
    private $id = null;

    function doMatch($url, $parts) {
        if (isset($parts[0]) && $parts[0] == 'maandplanning') {
            if (count($parts) == 1) {
                return true;
            } elseif ($parts[1] == 'edit' && count($parts) <= 3) {
                if (count($parts) == 3) {
                    if (!is_numeric($parts[2])) {
                        return false;
                    }
                    $this->id = intval($parts[2]);
                }
                return true;
            } elseif ($parts[1] == 'delete' && count($parts) == 3) {
                if (!is_numeric($parts[2])) {
                    return false;
                }
                $this->id = intval($parts[2]);
                return true;
            }
        }

        return false;
    }

    function getPage($url, $parts) {
        if (count($parts) == 1) {
            require(__DIR__.'/admin/overview.php');
            return new Admin\Overview();
        }
        if ($parts[1] == 'edit') {
            require(__DIR__.'/admin/edit.php');
            return new Admin\Edit($this->id);
        }
        require(__DIR__.'/admin/delete.php');
        return new Admin\Delete($this->id);
    }
}