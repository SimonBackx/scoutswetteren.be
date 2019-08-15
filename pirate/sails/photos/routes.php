<?php
namespace Pirate\Sails\Photos;
use Pirate\Wheel\Page;
use Pirate\Wheel\Route;
use Pirate\Sails\Files\Models\Album;

class PhotosRouter extends Route {
    private $tak = null;
    private $album = null;
    function doMatch($url, $parts) {
        if ($url == 'fotos') {
            return true;
        }
        if (count($parts) == 2 && $parts[0] == 'fotos') {
            if (in_array($parts[1], Album::getGroups())) {
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
                // Downloaden enkel toestaan als sources beschikbaar zijn, of er een zip file bestaat
                if (!$album->sources_available && !isset($album->zip_file)) {
                    return false;
                }
                
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