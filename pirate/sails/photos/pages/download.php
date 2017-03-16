<?php
namespace Pirate\Sail\Photos\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Files\Album;
use Pirate\Model\Files\Image;
use Pirate\Model\Files\File;

class DownloadAlbum extends Page {
    private $album = null;

    function __construct(Album $album) {
        $this->album = $album;
    }

    function getStatusCode() {
        $zip = $this->album->createZip();
        if (isset($zip)) {
            return 301;
        }
        return 404;
    }

    function getContent() {
        if (isset($this->album->zip_file)) {
            $file = File::getFile($this->album->zip_file);
            if (isset($file)) {
                header('Location: '.$file->getPublicPath());
                return 'Doorverwijzen naar '.$file->getPublicPath();
            } else {
                return 'Dit album kan niet (meer) gedownload worden.';
            }
        }
        return 'Dit album kan niet (meer) gedownload worden.';
    }
}