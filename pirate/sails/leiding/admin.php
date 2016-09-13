<?php

namespace Pirate\Sail\Leiding;
use Pirate\Page\Page;
use Pirate\Route\Route;
use Pirate\Model\Leiding\Leiding;

class LeidingAdminRouter extends Route {
    private $leiding = null;

    function doMatch($url, $parts) {
        if (empty($url)) {
            return true;
        }
        if ($url == 'wachtwoord-wijzigen') {
            return true;
        }
        
        if (!Leiding::hasPermission('groepsleiding') && !Leiding::hasPermission('webmaster')) {
            return false;
        }

        if ($url == 'leiding') {
            return true;
        }
        if (isset($parts[1]) && $parts[0] == 'leiding' && ($parts[1] == 'edit' || $parts[1] == 'delete')) {
            if (isset($parts[2])) {
                if (!is_numeric($parts[2])) {
                    return false;
                }
                $id = intval($parts[2]);
                $this->leiding = Leiding::getLeidingById($id);
                if (isset($this->leiding)) {
                    return true;
                }
                return false;
            }

            if ($parts[1] == 'edit') {
                $this->leiding = new Leiding();
                return true;
            }
            return false;
            
        }
        return false;
    }

    function getPage($url, $parts) {
        if (empty($url)) {
            require(__DIR__.'/admin/gegevens.php');
            return new Admin\Gegevens();
        }
        if ($url == 'leiding') {
            require(__DIR__.'/admin/overview.php');
            return new Admin\Overview();
        }
        if (isset($parts[1]) && $parts[0] == 'leiding') {
            if ($parts[1] == 'edit') {
                require(__DIR__.'/admin/gegevens.php');
                return new Admin\Gegevens($this->leiding);
            }
            require(__DIR__.'/admin/delete.php');
            return new Admin\Delete($this->leiding);
        }
        require(__DIR__.'/admin/wachtwoord-wijzigen.php');
        return new Admin\WachtwoordWijzigen();
    }
}