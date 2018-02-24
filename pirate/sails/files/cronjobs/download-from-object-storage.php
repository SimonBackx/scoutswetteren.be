<?php
namespace Pirate\Cronjob\Files;
use Pirate\Cronjob\Cronjob;

use Pirate\Model\Files\File;
use Pirate\Model\Files\ImageFile;
use Pirate\Model\Files\Image;
use Pirate\Model\Files\Album;

class DownloadFromObjectStorage extends Cronjob {
    function needsRunning() {
        return true;
    }

    function run() {
        $files = File::getFilesNotSavedOnServer();
        foreach ($files as $file) {
            $errors = array();
            echo "Downloading $file->name from object storage...\n";
            if ($file->downloadFromSpace($errors)) {
                echo "Succeeded.\n";
            } else {
                echo "FAIL: ".implode("\n", $errors)."\n";
            }
        }

    }
}


?>
