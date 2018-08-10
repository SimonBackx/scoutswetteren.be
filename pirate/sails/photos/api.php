<?php
namespace Pirate\Sail\Photos;
use Pirate\Page\Page;
use Pirate\Route\Route;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Files\Album;
use Pirate\Model\Files\Image;

class PhotosApiRouter extends Route {
    private $album = null;
    private $image = null;

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

        if (count($parts) == 2 && $parts[0] == 'update-sources-available') {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return false;
            }

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

        if (count($parts) == 2 && ($parts[0] == 'delete' || $parts[0] == 'set-cover' || $parts[0] == 'set-title')) {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return false;
            }

            $id = $parts[1];
            $this->image = Image::getImage($id);
            if (isset($this->image)) {
                return true;
            }
            return false;
        }

        return false;
    }

    function getPage($url, $parts) {
        if (isset($this->image)) {
            if ($parts[0] == 'delete') {
                require(__DIR__.'/api/delete.php');
                return new Api\DeletePhoto($this->image);
            }

            if ($parts[0] == 'set-title') {
                require(__DIR__.'/api/set-title.php');
                return new Api\SetTitle($this->image);
            }

            require(__DIR__.'/api/set-cover.php');
            return new Api\SetCover($this->image);
        }
        if (count($parts) == 2 && $parts[0] == 'update-sources-available') {
            require(__DIR__.'/api/update-sources-available.php');
            return new Api\UpdateSourcesAvailable($this->album);
        }

        require(__DIR__.'/api/upload-photo.php');
        return new Api\UploadPhoto($this->album);
    }
}