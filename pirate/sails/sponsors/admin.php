<?php
namespace Pirate\Sail\Sponsors;
use Pirate\Page\Page;
use Pirate\Route\AdminRoute;
use Pirate\Model\Sponsors\Sponsor;
use Pirate\Model\Leiding\Leiding;

class SponsorsAdminRouter extends AdminRoute {
    private $sponsor = null;

    static function getAvailablePages() {
        return [
            'oudercomite' => array(
                array('name' => 'Sponsors', 'url' => 'sponsors')
            ),
            'groepsleiding' => array(
                array('name' => 'Sponsors', 'url' => 'sponsors')
            ),
        ];
    }

    function doMatch($url, $parts) {
        if (!Leiding::hasPermission('sponsors')) {
            return false;
        }
        
        if (count($parts) >=2 && count($parts) <= 3 && $parts[0] == 'sponsors' && $parts[1] == 'edit') {
            if (isset($parts[2])) {
                if (!is_numeric($parts[2])) {
                    return false;
                }
                
                $this->sponsor = Sponsor::getSponsor(intval($parts[2]));
                return isset($this->sponsor);
            }
            
            // New
            return true;
        }

        if (count($parts) == 3 && $parts[0] == 'sponsors' && $parts[1] == 'delete') {
            if (!is_numeric($parts[2])) {
                return false;
            }
            
            $this->sponsor = Sponsor::getSponsor(intval($parts[2]));
            return isset($this->sponsor);
        }

        if (count($parts) == 1 && $parts[0] == 'sponsors') {
            return true;
        }

        return false;
    }

    function getPage($url, $parts) {
        if (isset($parts[1]) && $parts[1] == 'edit') {
            require(__DIR__.'/admin/edit.php');
            return new Admin\Edit($this->sponsor);
        }

        if (isset($parts[1]) && $parts[1] == 'delete') {
            require(__DIR__.'/admin/delete.php');
            return new Admin\Delete($this->sponsor);
        }

        require(__DIR__.'/admin/overview.php');
        return new Admin\Overview();
    }
}