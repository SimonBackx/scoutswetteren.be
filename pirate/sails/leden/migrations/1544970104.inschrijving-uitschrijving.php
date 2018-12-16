<?php
namespace Pirate\Classes\Leden;
use Pirate\Classes\Migrations\Migration;

class InschrijvingUitschrijving1544970104 extends Migration {

    static function upgrade(): bool {
        $create_query = "ALTER TABLE inschrijvingen
        ADD COLUMN `datum_uitschrijving` datetime DEFAULT NULL;";
        
        if (!self::getDb()->query($create_query)) {
            throw new \Exception(self::getDb()->error);
        }

        return true;
    }

    static function downgrade(): bool {
        throw new \Exception("Migration downgrade is not implemented");
    }

}