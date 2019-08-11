<?php
namespace Pirate\Sails\Leden\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class Lidnummer1530213254 extends Migration
{

    public static function upgrade(): bool
    {
        $create_query = "ALTER TABLE leden
            ADD COLUMN `lidnummer` int(14) unsigned AFTER `id`;";

        // Todo: foreign key toevoegen

        if (!self::getDb()->query($create_query)) {
            throw new \Exception(self::getDb()->error);
        }

        return true;
    }

    public static function downgrade(): bool
    {
        throw new \Exception("Migration downgrade is not implemented");
    }

}
