<?php
namespace Pirate\Sails\Files\Migrations;

use Pirate\Sails\Files\Models\File;
use Pirate\Sails\Migrations\Classes\Migration;

class RecoverMissingFiles1572175400 extends Migration
{

    public static function upgrade(): bool
    {
        $files = File::getMissingFiles();
        foreach ($files as $file) {
            if ($file->recoverFromSpace()) {
                echo "Recovered file " . $file->getPublicPath() . "\n";
            }
        }
        return true;
    }

    public static function downgrade(): bool
    {
        throw new \Exception("Migration downgrade is not implemented");
    }

}
