<?php
namespace Pirate\Cronjob\Files;
use Pirate\Cronjob\Cronjob;

use Pirate\Model\Files\File;
use Pirate\Model\Files\ImageFile;
use Pirate\Model\Files\Image;
use Pirate\Model\Files\Album;

class ZipAlbums extends Cronjob {
    private $albums;

    function needsRunning() {        
        $this->albums = Album::getZippableAlbums();
        return isset($this->albums) && count($this->albums) > 0;
    }

    function run() {
        foreach ($this->albums as $album) {
            $errors = array();
            echo "Zipping album $album->name...\n";
            if ($album->zip($errors)) {
                echo "Succeeded\n";
            } else {
                echo "FAIL: ".implode("\n", $errors)."\n";
            }
        }
    }
}


?>
