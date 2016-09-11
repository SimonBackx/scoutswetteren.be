<?php
namespace Pirate\Sail\Verhuur;
use Pirate\Page\Page;
use Pirate\Route\Route;

class VerhuurAdminRouter extends Route {
    private $id = null;

    function doMatch($url, $parts) {
        if (isset($parts[0]) && $parts[0] == 'verhuur') {
            if (count($parts) == 1) {
                return true;
            } elseif ($parts[1] == 'reservatie' && count($parts) <= 3) {
                if (count($parts) == 3) {
                    if (!is_numeric($parts[2])) {
                        return false;
                    }
                    $this->id = intval($parts[2]);
                }
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
        require(__DIR__.'/admin/edit.php');
        return new Admin\Edit($this->id);
    }
}