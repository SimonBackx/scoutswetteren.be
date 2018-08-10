<?php
namespace Pirate\Cronjob\Files;
use Pirate\Cronjob\Cronjob;

use Pirate\Model\Files\File;
use Pirate\Model\Files\ImageFile;
use Pirate\Model\Files\Image;
use Pirate\Model\Files\Album;

class UploadToObjectStorage extends Cronjob {
    function needsRunning() {
        return true;
    }

    function run() {
        $files = File::getFilesNotObjectStorage();
        foreach ($files as $file) {
            $errors = array();
            echo "Uploading $file->name to object storage...\n";
            if ($file->uploadToSpace($errors)) {
                echo "Succeeded.\n";
            } else {
                echo "FAIL: ".implode("\n", $errors)."\n";
            }
        }
    }
}


?>
