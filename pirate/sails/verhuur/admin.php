<?php
namespace Pirate\Sails\Verhuur;
use Pirate\Wheel\Page;
use Pirate\Wheel\AdminRoute;
use Pirate\Sails\Leiding\Models\Leiding;

class VerhuurAdminRouter extends AdminRoute {
    private $id = null;
    private $future_only = true;

    static function getAvailablePages() {
        return [
            '' => array(
                array('name' => 'Verhuur', 'url' => 'verhuur')
            ),
            'verhuur' => array(
                array('priority' => 100, 'name' => 'Verhuur', 'url' => 'verhuur')
            ),
            'oudercomite' => array(
                array('priority' => 100, 'name' => 'Verhuur', 'url' => 'verhuur'),
            ),
            'materiaalmeester' => array(
                array('priority' => 1, 'name' => 'Materiaal', 'url' => 'materiaal')
            )
        ];
    }

    function doMatch($url, $parts) {
        if (Leiding::hasPermission('materiaalmeester') && count($parts) == 1 && $parts[0] == 'materiaal') {
            return true;
        }

        if (!Leiding::hasPermission('verhuur') && !Leiding::hasPermission('oudercomite') && !Leiding::hasPermission('leiding') && !Leiding::hasPermission('groepsleiding')) {
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
        if (count($parts) == 1 && $parts[0] == 'materiaal') { 
            require(__DIR__.'/admin/materiaal.php');
            return new Admin\Materiaal();
        }

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