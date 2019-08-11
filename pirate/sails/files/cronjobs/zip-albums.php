<?php
namespace Pirate\Sails\Files\Cronjobs;

use Pirate\Sails\Files\Models\Album;
use Pirate\Wheel\Cronjob;

class ZipAlbums extends Cronjob
{
    private $albums;

    public function needsRunning()
    {
        $this->albums = Album::getZippableAlbums();
        return isset($this->albums) && count($this->albums) > 0;
    }

    public function run()
    {
        foreach ($this->albums as $album) {
            $errors = array();
            echo "Zipping album $album->name...\n";
            if ($album->zip($errors)) {
                echo "Succeeded\n";
            } else {
                echo "FAIL: " . implode("\n", $errors) . "\n";
            }
        }
    }
}
