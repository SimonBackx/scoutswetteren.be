<?php
namespace Pirate\Sails\Files\Cronjobs;

use Pirate\Sails\Files\Models\File;
use Pirate\Wheel\Cronjob;

class DownloadFromObjectStorage extends Cronjob
{
    public function needsRunning()
    {
        return true;
    }

    public function run()
    {
        $files = File::getFilesNotSavedOnServer();
        foreach ($files as $file) {
            $errors = array();
            echo "Downloading $file->name from object storage...\n";
            if ($file->downloadFromSpace($errors)) {
                echo "Succeeded.\n";
            } else {
                echo "FAIL: " . implode("\n", $errors) . "\n";
            }
        }

    }
}
