<?php
namespace Pirate\Sails\Files\Cronjobs;

use Pirate\Sails\Files\Models\File;
use Pirate\Wheel\Cronjob;

class DeleteFromServer extends Cronjob
{
    public function needsRunning()
    {
        return true;
    }

    public function run()
    {
        $files = File::getRemoveableFiles();

        foreach ($files as $file) {
            $errors = array();
            echo "Deleting $file->name from server...\n";
            if ($file->deleteFromServer($errors)) {
                echo "Succeeded.\n";
            } else {
                echo "FAIL: " . implode("\n", $errors) . "\n";
            }
        }
    }
}
