<?php

namespace Pirate\Sails\Leiding;
use Pirate\Wheel\Page;
use Pirate\Wheel\AdminRoute;
use Pirate\Sails\Leiding\Models\Leiding;

class LeidingAdminRouter extends AdminRoute {
    private $leiding = null;

    static function getAvailablePages() {
        return [
            '' => [
                array('priority' => 1000, 'name' => 'Ik', 'url' => ''),
            ],
            'groepsleiding' => [
                array('priority' => 200, 'name' => 'Leiding', 'url' => 'leiding'),
            ],
        ];
    }

    function doMatch($url, $parts) {
        if (empty($url)) {
            return true;
        }

        if (!Leiding::hasPermission('groepsleiding') && !Leiding::hasPermission('webmaster')) {
            return false;
        }

        if ($url == 'leiding') {
            return true;
        }

        if (isset($parts[1]) && $parts[0] == 'leiding' && $parts[1] == 'verdeling') {
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
        if ($url == 'leiding') {
            require(__DIR__.'/admin/overview.php');
            return new Admin\Overview();
        }
        
        if (isset($parts[1]) && $parts[0] == 'leiding' && $parts[1] == 'verdeling') {
            require(__DIR__.'/admin/leidingsverdeling.php');
            return new Admin\Leidingsverdeling();
        }

        if (isset($parts[1]) && $parts[0] == 'leiding') {
            if ($parts[1] == 'edit') {
                require(__DIR__.'/admin/gegevens.php');
                return new Admin\Gegevens($this->leiding);
            }
            require(__DIR__.'/admin/delete.php');
            return new Admin\Delete($this->leiding);
        }

        require(__DIR__.'/admin/gegevens.php');
        return new Admin\Gegevens();
    }
}