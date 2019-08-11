<?php
namespace Pirate\Sails\Photos;
use Pirate\Wheel\Page;
use Pirate\Wheel\AdminRoute;
use Pirate\Sails\Files\Models\Album;
use Pirate\Sails\Leiding\Models\Leiding;

class PhotosAdminRouter extends AdminRoute {
    private $album = null;

    static function getAvailablePages() {
        return [
            '' => array(
                array('name' => 'Foto\'s', 'url' => 'photos'),
            ),
        ];
    }

    function doMatch($url, $parts) {
        if ($url == 'photos/upload') {
            return true;
        }

        if ($url == 'photos/remove-sources') {
            if (Leiding::hasPermission('webmaster')) {
               return true; 
            }
            return false;
        }

        if (count($parts) == 3 && $parts[0] == 'photos' && $parts[1] == 'edit') {
            $id = $parts[2];
            $this->album = Album::getAlbum($id);
            if (isset($this->album)) {
                return true;
            }
            return false;
        }

        if (count($parts) == 3 && $parts[0] == 'photos' && $parts[1] == 'delete') {
            $id = $parts[2];
            $this->album = Album::getAlbum($id);
            if (isset($this->album)) {
                return true;
            }
            return false;
        }

        if ($url == 'photos') {
            return true;
        }

        return false;
    }

    function getPage($url, $parts) {
        if ($url == 'photos') {
            require(__DIR__.'/admin/overview.php');
            return new Admin\Overview();
        }

        if ($url == 'photos/remove-sources') {
            require(__DIR__.'/admin/remove-sources.php');
            return new Admin\RemoveSources();
        }

        if (isset($this->album)) {
            if ($parts[1] == 'delete') {
                require(__DIR__.'/admin/delete.php');
                return new Admin\Delete($this->album);
            }
            
            require(__DIR__.'/admin/edit.php');
            return new Admin\Edit($this->album);
        }

        require(__DIR__.'/admin/upload.php');
        return new Admin\Upload();
    }
}