<?php
namespace Pirate\Sail\Sponsors;
use Pirate\Page\Page;
use Pirate\Route\Route;
use Pirate\Model\Sponsors\Sponsor;
use Pirate\Model\Leiding\Leiding;

class SponsorsAdminRouter extends Route {
    private $sponsor = null;

    function doMatch($url, $parts) {
        if (!Leiding::hasPermission('sponsors')) {
            return false;
        }
        
        if (count($parts) >=2 && count($parts) <= 3 && $parts[0] == 'sponsors' && $parts[1] == 'sponsor') {
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

        return false;
    }

    function getPage($url, $parts) {
        if (isset($parts[1]) && $parts[1] == 'sponsor') {
            require(__DIR__.'/admin/edit.php');
            return new Admin\Edit($this->sponsor);
        }

        // todo: overview
        return false;
    }
}