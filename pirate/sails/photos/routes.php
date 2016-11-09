<?php
namespace Pirate\Sail\Photos;
use Pirate\Page\Page;
use Pirate\Route\Route;
use Pirate\Model\Files\Album;

class PhotosRouter extends Route {
    private $tak = null;
    private $album = null;
    function doMatch($url, $parts) {
        if ($url == 'fotos') {
            return true;
        }
        if (count($parts) == 2 && $parts[0] == 'fotos') {
            if (in_array($parts[1], Album::$groups)) {
                $this->tak = $parts[1];
                return true;
            }
            return false;
        }

        if (count($parts) == 6 && $parts[0] == 'fotos' && $parts[1] == 'album') {
            $album = Album::getAlbumBySlug($parts[2], $parts[3], $parts[4], $parts[5]);

            if (isset($album)) {
                $this->album = $album;
                return true;
            }
            return false;
        }

        if (count($parts) == 6 && $parts[0] == 'fotos' && $parts[1] == 'download') {
            $album = Album::getAlbumBySlug($parts[2], $parts[3], $parts[4], $parts[5]);

            if (isset($album)) {
                $this->album = $album;
                return true;
            }
            return false;
        }

        return false;
    }

    function getPage($url, $parts) {
        if (isset($this->album)) {
            if ($parts[1] == 'download') {
                require(__DIR__.'/pages/download.php');
                return new Pages\DownloadAlbum($this->album);
            }
            require(__DIR__.'/pages/album.php');
            return new Pages\AlbumOverview($this->album);
        }
        require(__DIR__.'/pages/albums.php');
        return new Pages\Albums($this->tak);
    }
}