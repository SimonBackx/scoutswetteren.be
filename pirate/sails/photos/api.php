<?php
namespace Pirate\Sail\Photos;
use Pirate\Page\Page;
use Pirate\Route\Route;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Files\Album;

class PhotosApiRouter extends Route {
    private $album = null;

    function doMatch($url, $parts) {
        if (!Leiding::isLoggedIn()) {
            return false;
        }
        
        if (count($parts) == 2 && $parts[0] == 'upload') {
            $id = $parts[1];
            $this->album = Album::getAlbum($id);
            if (isset($this->album)) {
                return true;
            }
            return false;
        }

        if (count($parts) == 1 && $parts[0] == 'upload') {
            return true;
        }

        return false;
    }

    function getPage($url, $parts) {
        require(__DIR__.'/api/upload-photo.php');
        return new Api\UploadPhoto($this->album);
    }
}