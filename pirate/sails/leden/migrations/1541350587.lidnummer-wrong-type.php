<?php
namespace Pirate\Sails\Leden\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class LidnummerWrongType1541350587 extends Migration
{

    public static function upgrade(): bool
    {
        $create_query = "ALTER TABLE leden
            MODIFY `lidnummer` varchar(50)";

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
