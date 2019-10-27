<?php
namespace Pirate\Sails\Files\Migrations;

use Pirate\Sails\Files\Models\Album;
use Pirate\Sails\Migrations\Classes\Migration;

class UpdateSourcesAvailable1572173002 extends Migration
{

    public static function upgrade(): bool
    {
        $albums = Album::getAlbums();
        foreach ($albums as $album) {
            echo "$album->name\n";
            $album->updateSourcesAvailable();
            if (!$album->sources_available && !isset($album->zip_file)) {
                echo "\t!!!!! Sources not available and no zip file.\n";
                $album->setSourcesShouldBeSavedOnServer(true);
            }
        }
        return true;
    }

    public static function downgrade(): bool
    {
        throw new \Exception("Migration downgrade is not implemented");
    }

}
