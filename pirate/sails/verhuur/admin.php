<?php
namespace Pirate\Sail\Verhuur;
use Pirate\Page\Page;
use Pirate\Route\Route;
use Pirate\Model\Leiding\Leiding;

class VerhuurAdminRouter extends Route {
    private $id = null;
    private $future_only = true;

    function doMatch($url, $parts) {
        if (!Leiding::hasPermission('verhuur') && !Leiding::hasPermission('oudercomite')) {
            return false;
        }
        
        if (isset($parts[0]) && $parts[0] == 'verhuur') {
            if (count($parts) == 2 && $parts[1] == 'geschiedenis') {
                $this->future_only = false;
                return true;
            } elseif (count($parts) == 1) {
                return true;
            } elseif ($parts[1] == 'reservatie' && count($parts) <= 3) {
                if (count($parts) == 3) {
                    if (!is_numeric($parts[2])) {
                        return false;
                    }
                    $this->id = intval($parts[2]);
                }
                return true;
            } elseif ($parts[1] == 'delete' && count($parts) <= 3) {
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
        if (isset($parts[1]) && $parts[1] == 'reservatie') {
            require(__DIR__.'/admin/edit.php');
            return new Admin\Edit($this->id);
        }

        if (isset($parts[1]) && $parts[1] == 'delete') {
            require(__DIR__.'/admin/delete.php');
            return new Admin\Delete($this->id);  
        }
        
        require(__DIR__.'/admin/overview.php');
        return new Admin\Overview($this->future_only);
    }
}