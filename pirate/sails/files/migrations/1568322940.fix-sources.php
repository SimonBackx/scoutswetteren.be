<?php
namespace Pirate\Sails\Files\Migrations;

use Pirate\Sails\Files\Models\Album;
use Pirate\Sails\Migrations\Classes\Migration;

class FixSources1568322940 extends Migration
{

    public static function upgrade(): bool
    {
        $need_sources = Album::getZippableAlbums(false);

        if (isset($need_sources)) {
            // Autofix albums that need sources
            foreach ($need_sources as $album) {
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
