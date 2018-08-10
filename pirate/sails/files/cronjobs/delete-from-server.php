<?php
namespace Pirate\Cronjob\Files;
use Pirate\Cronjob\Cronjob;

use Pirate\Model\Files\File;
use Pirate\Model\Files\ImageFile;
use Pirate\Model\Files\Image;
use Pirate\Model\Files\Album;

class DeleteFromServer extends Cronjob {
    function needsRunning() {
        return true;
    }

    function run() {
        $files = File::getRemoveableFiles();

        foreach ($files as $file) {
            $errors = array();
            echo "Deleting $file->name from server...\n";
            if ($file->deleteFromServer($errors)) {
                echo "Succeeded.\n";
            } else {
                echo "FAIL: ".implode("\n", $errors)."\n";
            }
        }
    }
}


?>
