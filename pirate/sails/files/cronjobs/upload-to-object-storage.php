<?php
namespace Pirate\Sails\Files\Cronjobs;

use Pirate\Sails\Files\Models\File;
use Pirate\Wheel\Cronjob;

class UploadToObjectStorage extends Cronjob
{
    public function needsRunning()
    {
        return true;
    }

    public function run()
    {
        $files = File::getFilesNotObjectStorage();
        foreach ($files as $file) {
            $errors = array();
            echo "Uploading $file->name to object storage...\n";
            if ($file->uploadToSpace($errors)) {
                echo "Succeeded.\n";
            } else {
                echo "FAIL: " . implode("\n", $errors) . "\n";
            }
        }
    }
}
