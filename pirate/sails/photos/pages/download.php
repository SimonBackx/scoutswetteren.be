<?php
namespace Pirate\Sails\Photos\Pages;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Files\Models\Album;
use Pirate\Sails\Files\Models\Image;
use Pirate\Sails\Files\Models\File;

class DownloadAlbum extends Page {
    private $album = null;
    private $up_to_date = false;

    function __construct(Album $album) {
        $this->album = $album;
        $this->up_to_date = $this->album->isZipUpToDate();
    }

    function getStatusCode() {
        if (!$this->up_to_date) {
            return 404;
        }

        if (!isset($this->album->zip_file)) {
            return 400;
        }

        return 302;
    }

    function getContent() {
        if (!$this->up_to_date) {
            return 'Dit album kan binnenkort terug gedownload worden maar is momenteel niet meer up to date.';
        }

        if (isset($this->album->zip_file)) {
            $file = File::getFile($this->album->zip_file);
            if (isset($file)) {
                header('Location: '.$file->getPublicPath());
                return 'Doorverwijzen naar '.$file->getPublicPath();
            } else {
                return 'Dit album kan niet (meer) gedownload worden. Het kan even duren voor een album beschikbaar wordt om te worden gedownload nadat er foto\'s werden toegevoegd.';
            }
        }
        return 'Dit album kan niet (meer) gedownload worden. Het kan even duren voor een album beschikbaar wordt om te worden gedownload nadat er foto\'s werden toegevoegd.';
    }
}